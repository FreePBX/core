<?php
// vim: set ai ts=4 sw=4 ft=php:

class Core extends BMO {

	public function __construct($freepbx = null) {
		if ($freepbx == null)
			throw new Exception("Not given a FreePBX Object");

		//Hackery-Jackery for Core only really
		if(!class_exists('PJSip') && file_exists(__DIR__.'/functions.inc/PJSip.class.php')) {
			include(__DIR__.'/functions.inc/PJSip.class.php');
			FreePBX::create()->PJSip = new PJSip(FreePBX::create());
		}
	}
	
	public function install() {
		
	}
	public function uninstall() {
		
	}
	public function showPage($request) {
		
	}
	public function backup(){
		
	}
	public function restore($backup){
		
	}
	public function getConfig() {
		
	}
	public function writeConfig($config){
		
	}
	
	public function doConfigPageInit($page) {
	
	}
	
	public function doGuiHook(&$currentconfig) {
	}
}
