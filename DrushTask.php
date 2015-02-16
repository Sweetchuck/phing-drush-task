<?php

/**
 * @file
 * A Phing task to run Drush commands.
 */
require_once "phing/Task.php";

/**
 * Class DrushParam.
 */
class DrushParam {

  private $value;

  public function addText($str) {
    $this->value = $str;
  }

  public function getValue() {
    return $this->value;
  }

}

/**
 * Class DrushOption.
 */
class DrushOption {

  private $name;
  private $value;

  public function setName($str) {
    $this->name = $str;
  }

  public function getName() {
    return $this->name;
  }

  public function addText($str) {
    $this->value = $str;
  }

  public function getValue() {
    return $this->value;
  }

  public function __toString() {
    $name  = $this->getName();
    $value = $this->getValue();
    $str = '--'.$name;
    if (!empty($value)) {
      $str .= '='.$value;
    }
    return $str;
  }

}

/**
 * Class DrushTask.
 */
class DrushTask extends Task {

  /**
   * The message passed in the build file.
   */
  private $command = array();

  /**
   * @var string
   */
  private $bin = '';

  /**
   * @var string
   */
  private $uri = '';

  /**
   * @var string
   */
  private $root = '';

  /**
   * @var bool|null
   */
  private $assume = NULL;

  /**
   * @var bool
   */
  private $simulate = FALSE;

  /**
   * @var bool
   */
  private $pipe = FALSE;

  /**
   * @var DrushOption[]
   */
  private $options = array();

  /**
   * @var DrushParam[]
   */
  private $params = array();

  /**
   * @var string
   */
  private $returnGlue = "\n";

  /**
   * @var string
   */
  private $returnProperty = NULL;

  /**
   * @var bool
   */
  private $verbose = FALSE;

  /**
   * @var bool
   */
  private $haltOnError = TRUE;

  /**
   * @var string
   */
  protected $cwd = '';

  /**
   * The Drush command to run.
   */
  public function setCommand($str) {
    $this->command = $str;
  }

  /**
   * Path the Drush executable.
   */
  public function setBin($str) {
    $this->bin = $str;
  }

  /**
   * Drupal root directory to use.
   */
  public function setRoot($str) {
    $this->root = $str;
  }

  /**
   * URI of the Drupal to use.
   */
  public function setUri($str) {
    $this->uri = $str;
  }

  /**
   * Assume 'yes' or 'no' to all prompts.
   */
  public function setAssume($var) {
    if (is_string($var)) {
      $this->assume = ($var === 'yes');
    } else {
      $this->assume = !!$var;
    }
  }

  /**
   * Simulate all relevant actions.
   */
  public function setSimulate($var) {
    if (is_string($var)) {
      $var = strtolower($var);
      $this->simulate = ($var === 'yes' || $var === 'true');
    } else {
      $this->simulate = !!$var;
    }
  }

  /**
   * Use the pipe option.
   */
  public function setPipe($var) {
    if (is_string($var)) {
      $var = strtolower($var);
      $this->pipe = ($var === 'yes' || $var === 'true');
    } else {
      $this->pipe = !!$var;
    }
  }

  /**
   * The 'glue' characters used between each line of the returned output.
   */
  public function setReturnGlue($str) {
    $this->returnGlue = (string) $str;
  }

  /**
   * The name of a Phing property to assign the Drush command's output to.
   */
  public function setReturnProperty($str) {
    $this->returnProperty = $str;
  }

  /**
   * @param string $var
   */
  public function setHaltOnError($var) {
    if (is_string($var)) {
      $var = strtolower($var);
      $this->haltOnError = ($var === 'yes' || $var === 'true');
    } else {
      $this->haltOnError = !!$var;
    }
  }

  /**
   * @param string $value
   *
   * @return $this
   */
  public function setDir($value) {
    $this->cwd = $value;

    return $this;
  }

  /**
   * Parameters for the Drush command.
   */
  public function createParam() {
    $o = new DrushParam();
    $this->params[] = $o;
    return $o;
  }

  /**
   * Options for the Drush command.
   */
  public function createOption() {
    $o = new DrushOption();
    $this->options[] = $o;
    return $o;
  }

  /**
   * Display extra information about the command.
   */
  public function setVerbose($var) {
    if (is_string($var)) {
      $this->verbose = ($var === 'yes');
    } else {
      $this->verbose = !!$var;
    }
  }

  /**
   * Initialize the task.
   */
  public function init() {
    // Get default root, uri and binary from project.
    $this->root = $this->getProject()->getProperty('drush.root');
    $this->uri = $this->getProject()->getProperty('drush.uri');
    $this->bin = $this->getProject()->getProperty('drush.bin');
  }

  /**
   * The main entry point method.
   */
  public function main() {
    $command = array();

    $command[] = !empty($this->bin) ? $this->bin : 'drush';

    $option = new DrushOption();
    $option->setName('nocolor');
    $this->options[] = $option;

    if (!empty($this->root)) {
      $option = new DrushOption();
      $option->setName('root');
      $option->addText($this->root);
      $this->options[] = $option;
    }

    if (!empty($this->uri)) {
      $option = new DrushOption();
      $option->setName('uri');
      $option->addText($this->uri);
      $this->options[] = $option;
    }

    if (is_bool($this->assume)) {
      $option = new DrushOption();
      $option->setName(($this->assume ? 'yes' : 'no'));
      $this->options[] = $option;
    }

    if ($this->simulate) {
      $option = new DrushOption();
      $option->setName('simulate');
      $this->options[] = $option;
    }

    if ($this->pipe) {
      $option = new DrushOption();
      $option->setName('pipe');
      $this->options[] = $option;
    }

    if ($this->verbose) {
      $option = new DrushOption();
      $option->setName('verbose');
      $this->options[] = $option;
    }

    foreach ($this->options as $option) {
      $command[] = (string) $option;
    }

    $command[] = $this->command;

    foreach ($this->params as $param) {
      $command[] = $param->getValue();
    }

    $command = implode(' ', $command);

    // Execute Drush.
    $this->log("Executing '$command'...");
    $error_code = 0;
    $output = array();

    $original_directory = NULL;
    if ($this->cwd) {
      $original_directory = getcwd();
      chdir($this->cwd);
    }

    exec($command, $output, $error_code);

    if ($original_directory) {
      chdir($original_directory);
    }

    // Collect Drush output for display through Phing's log.
    foreach ($output as $line) {
      $this->log($line);
    }

    // Set value of the 'pipe' property.
    if (!empty($this->returnProperty)) {
      $this->getProject()->setProperty($this->returnProperty, implode($this->returnGlue, $output));
    }

    // Build fail.
    if ($this->haltOnError && $error_code != 0) {
      throw new BuildException("Drush exited with code $error_code");
    }

    return $error_code != 0;
  }

}

