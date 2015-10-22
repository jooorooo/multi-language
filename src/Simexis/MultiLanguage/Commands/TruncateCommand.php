<?php 

namespace Simexis\MultiLanguage\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Simexis\MultiLanguage\Manager;

class TruncateCommand extends Command {

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $name = 'translator:truncate';

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Truncate translations.";

	/*
	* @var \Simexis\MultiLanguage\Manager
	*/
	private $manager;
	
	/**
	*  Create a new mixed loader instance.
	*
	*  @param  \Simexis\MultiLanguage\Manager $manager
	*/
	public function __construct(Manager $manager)
	{
		parent::__construct();
		$this->manager = $manager;
	}

	/**
	* Execute the console command.
	*
	* @return void
	*/
	public function fire()
	{
		$this->manager->truncateTranslations();
	}
}