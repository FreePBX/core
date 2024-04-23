<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Convert2pjsip extends Command {
	protected function configure(){
		$this->setName('convert2pjsip')
		->setDescription(_('Convert legacy chan_sip extensions to chan_pjsip'))
		->setDefinition(array(
			new InputOption('all', 'a', InputOption::VALUE_NONE, _('Convert all extensions to PJSIP')),
			new InputOption('range', 'r', InputOption::VALUE_REQUIRED, _('Specify a range of extensions to convert to PJSIP. Example: 5000-5100,6020,6030,6040')),
		));
	}

	protected function execute(InputInterface $input, OutputInterface $output){
		$convertAllExtensions = $input->getOption('all');
		$rangeArgs = $input->getOption('range');

		if (!$convertAllExtensions && !$rangeArgs) {
			$help = new HelpCommand();
			$help->setCommand($this);
			$help->run($input, $output);
			return 1;
		}

		// get an array of extensions to convert from arguments
		$convertExtensions = [];
		if($rangeArgs) {
			$convertExtensions = $this->convertArgsToArray($rangeArgs);
		}

		// get a list of all sip extensions
		$extensions = \FreePBX::Core()->getAllDevicesByType('sip');
		foreach($extensions as $exten) {
			if ($convertAllExtensions || in_array($exten['id'], $convertExtensions)) {
				try {
					\FreePBX::Core()->changeDeviceTech($exten['id'], 'pjsip');
					$output->writeln(sprintf(_('Converted extension %s to PJSIP'), $exten['id']));
				} catch(Exception $e) {
					$output->writeln(sprintf(_('There was an error converting extension %s to PJSIP:'), $exten['id']));
					$output->writeln($e->getMessage());
				}
			}
		}

		needreload();
		$output->writeln(_("Extensions converted successfully!"));
		$output->writeln(_("Run 'fwconsole reload' to reload config"));
		return 0;
	}

	private function isValidExtension($value) {
		if (!is_numeric($value)) {
			throw new \Exception("Invalid extension: {$value}");
			return false;
		}

		return true;
	}

	private function convertArgsToArray($args) {
		$argValues = preg_split('/,/', $args);

		$convertExtensions = array();
		foreach($argValues as $exten) {
			if (empty($exten)) {
				continue;
			}

			$rangeSplit = preg_split('/-/', $exten);
			if (count($rangeSplit) === 1) {
				$this->isValidExtension($exten);
				$convertExtensions[] = $exten;
				continue;
			}

			if (count($rangeSplit) > 2) {
				throw new \Exception("Invalid extension range: {$exten}");
			}

			$this->isValidExtension($rangeSplit[0]);
			$this->isValidExtension($rangeSplit[1]);
			$range = range($rangeSplit[0], $rangeSplit[1]);
			$convertExtensions = array_merge($range, $convertExtensions);
		}

		return array_values(array_unique($convertExtensions));
	}

}
