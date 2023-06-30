<?php
namespace FreePBX\Console\Command;
//Symfony stuff all needed add these
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
//Ask stuff
use Symfony\Component\Console\Question\ChoiceQuestion;
//la mesa
use Symfony\Component\Console\Helper\Table;

class Trunks extends Command {
	protected function configure(){
		$this->setName('trunks')
		->setDescription(_('Enable and disable trunks from the command line'))
		->setDefinition(array(
			new InputOption('enable', null, InputOption::VALUE_REQUIRED, _('Enable given trunk')),
			new InputOption('disable', null, InputOption::VALUE_REQUIRED, _('Disable given trunk')),
			new InputOption('list', null, InputOption::VALUE_NONE, _('list trunks')),
			new InputOption('xml', null, InputOption::VALUE_NONE, _('format list as json')),
			new InputOption('json', null, InputOption::VALUE_NONE, _('format list as xml')),
			new InputArgument('args', InputArgument::IS_ARRAY, '', null),));
	}
	protected function execute(InputInterface $input, OutputInterface $output){
		$args = $input->getArgument('args');
		$trunks = $this->listTrunks();
		$trunkids = array();
		foreach($trunks as $trunk){
			$trunkids[$trunk['trunkid']] =	$trunk['trunkid'];
		}
		if($input->getOption('disable')){
			$ARGUSED = True;
			$id = $input->getOption('disable');
			$output->writeln(sprintf(_('Disabling Trunk %s Run fwconsole reload'),$id));
			$this->disableTrunk($id);
		}
		if($input->getOption('enable')){
			$ARGUSED = True;
			$id = $input->getOption('enable');
			$output->writeln(sprintf(_('Enabling Trunk %s Run fwconsole reload'),$id));
			$this->enableTrunk($id);
		}
		if($input->getOption('list')){
			$ARGUSED = True;
			if($input->getOption('json')){
				$output->write(json_encode($trunks));
			}elseif($input->getOption('xml')){
				$xml = new \SimpleXMLElement('<trunks/>');
				array_walk_recursive($trunks, array ($xml, 'addChild'));
				$output->write($xml->asXML());
			}else{
				$table = new Table($output);
				$table->setHeaders(array('ID',_('TECH'),_('Channel ID'), _('Disabled')));
				$table->setRows($trunks);
				$table->render();
			}
		}
		if(!$ARGUSED){
			$table = new Table($output);
			$table->setHeaders(array('ID',_('TECH'),_('Channel ID'), _('Disabled')));
			$table->setRows($trunks);
			$output->writeln(_('Choose an ID to enable/disable'));
			$helper = $this->getHelper('question');
			$question = new ChoiceQuestion($table->render(),$trunkids,0);
			$id = $helper->ask($input, $output, $question);
			if($trunks[($id -1 )]['disabled'] == 'off'){
				$output->writeln(sprintf(_('Disabling Trunk %s'),$id));
				if($this->disableTrunk($id)){
					$output->writeln(sprintf(_('Disabled Trunk %s Run fwconsole reload'),$id));
				}else{
					$output->writeln(sprintf(_('Unable to enable Trunk %s. This trunk type may not support this')));
				}
			}
			if($trunks[($id -1)]['disabled'] == 'on'){
				$output->writeln(_('Enabling Trunk ') . $id);
				if($this->enableTrunk($id)){
					$output->writeln(sprintf(_('Enabled Trunk %s Run fwconsole reload'),$id));
				}else{
					$output->writeln(sprintf(_('Unable to enable Trunk %s. This trunk type may not support this')));
				}
			}
		}
	}
	private function listTrunks(){
		$db = \FreePBX::Database();
		$sql = "SELECT `trunkid` , `tech` , `channelid` , `disabled` FROM `trunks` ORDER BY `trunkid`";
		$ob = $db->query($sql,\PDO::FETCH_ASSOC);
		if($ob->rowCount()){
			$gotRows = $ob->fetchAll();
		}
		return $gotRows;
	}
	private function disableTrunk($id){
		return \FreePBX::Core()->disableTrunk($id);
	}
	private function enableTrunk($id){
		return \FreePBX::Core()->enableTrunk($id);
	}
}
