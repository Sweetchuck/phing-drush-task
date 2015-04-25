<?php

/**
 * @file
 * A Phing task to run Drush commands.
 */
require_once "phing/Task.php";

/**
 * Class DrushParam.
 */
class DrushParam
{

    /**
     * @var string
     */
    protected $value = NULL;

    /**
     * @param string $value
     */
    public function addText($value) {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

}

/**
 * Class DrushOption.
 */
class DrushOption {

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $value = '';

    /**
     * @param string $value
     */
    public function setName($value)
    {
        $this->name = $value;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $value
     */
    public function addText($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $name = $this->getName();
        $value = $this->getValue();
        $return = '--' . $name;
        if (!empty($value)) {
            $return .= '=' . $value;
        }

        return $return;
    }

}

/**
 * Class DrushTask.
 */
class DrushTask extends Task
{

    /**
     * @var string
     */
    protected $outputProperty = '';

    /**
     * The message passed in the build file.
     */
    protected $command = array();

    /**
     * @var string
     */
    protected $bin = '';

    /**
     * @var string
     */
    protected $alias = '';

    /**
     * @var string
     */
    protected $uri = '';

    /**
     * @var string
     */
    protected $root = '';

    /**
     * @var bool|null
     */
    protected $assume = NULL;

    /**
     * @var boolean
     */
    protected $simulate = FALSE;

    /**
     * @var boolean
     */
    protected $pipe = FALSE;

    /**
     * @var DrushOption[]
     */
    protected $options = array();

    /**
     * @var DrushParam[]
     */
    protected $params = array();

    /**
     * @var string
     */
    protected $outputGlue = "\n";

    /**
     * @var string
     */
    protected $returnProperty = NULL;

    /**
     * @var boolean
     */
    protected $verbose = FALSE;

    /**
     * @var boolean
     */
    protected $haltOnError = TRUE;

    /**
     * @var string
     */
    protected $dir = '';

    /**
     * The Drush command to run.
     *
     * @param string $value
     */
    public function setCommand($value)
    {
        $this->command = $value;
    }

    /**
     * Path the Drush executable.
     *
     * @param string $value
     */
    public function setBin($value)
    {
        $this->bin = $value;
    }

    /**
     * Name of the Drush alias without the leading @ character.
     *
     * @param string $value
     */
    public function setAlias($value)
    {
        $this->alias = $value;
    }

    /**
     * URI of the Drupal to use.
     *
     * @param string $value
     */
    public function setUri($value)
    {
        $this->uri = $value;
    }

    /**
     * Assume 'yes' or 'no' to all prompts.
     *
     * @param boolean $value
     */
    public function setAssume($value)
    {
        if (is_string($value)) {
            $this->assume = ($value === 'yes');
        }
        else {
            $this->assume = !!$value;
        }
    }

    /**
     * Simulate all relevant actions.
     *
     * @param boolean $value
     */
    public function setSimulate($value)
    {
        if (is_string($value)) {
            $value = strtolower($value);
            $this->simulate = ($value === 'yes' || $value === 'true');
        }
        else {
            $this->simulate = (bool) $value;
        }
    }

    /**
     * Use the pipe option.
     *
     * @param boolean $value
     */
    public function setPipe($value)
    {
        if (is_string($value)) {
            $value = strtolower($value);
            $this->pipe = ($value === 'yes' || $value === 'true');
        }
        else {
            $this->pipe = (bool) $value;
        }
    }

    /**
     * The 'glue' characters used between each line of the returned output.
     *
     * @param string $value
     */
    public function setOutputGlue($value)
    {
        $this->outputGlue = (string) $value;
    }

    /**
     * The name of a Phing property to assign the Drush command's output to.
     */
    public function setReturnProperty($str)
    {
        $this->returnProperty = $str;
    }

    /**
     * @param string $value
     */
    public function setOutputProperty($value)
    {
        $this->outputProperty = $value;
    }

    /**
     * @param boolean $value
     */
    public function setHaltOnError($value)
    {
        if (is_string($value)) {
            $value = strtolower($value);
            $this->haltOnError = ($value === 'yes' || $value === 'true');
        }
        else {
            $this->haltOnError = (bool) $value;
        }
    }

    /**
     * @param string $value
     */
    public function setRoot($value)
    {
        $this->root = $value;
    }

    /**
     * @param string $value
     */
    public function setDir($value)
    {
        $this->dir = $value;
    }

    /**
     * Display extra information about the command.
     *
     * @param boolean $value
     */
    public function setVerbose($value)
    {
        if (is_string($value)) {
            $this->verbose = ($value === 'yes');
        }
        else {
            $this->verbose = (bool) $value;
        }
    }

    /**
     * Parameters for the Drush command.
     */
    public function createParam()
    {
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
     * Initialize the task.
     */
    public function init()
    {
        $this->root = $this->getProject()->getProperty('drush.root');
        $this->dir = $this->getProject()->getProperty('drush.dir');
        $this->uri = $this->getProject()->getProperty('drush.uri');
        $this->bin = $this->getProject()->getProperty('drush.bin');
    }

    /**
     * The main entry point method.
     */
    public function main() {
        $command = array();

        $command[] = !empty($this->bin) ? $this->bin : 'drush';

        if ($this->alias) {
          $command[] = '@' . $this->alias;
        }

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

        if ($this->dir && !chdir($this->dir)) {
            throw new BuildException('Working directory is not exists.');
        }

        exec($command, $output, $error_code);

        if ($this->dir) {
            chdir($this->project->getBasedir());
        }

        // Collect Drush output for display through Phing's log.
        foreach ($output as $line) {
            $this->log($line);
        }

        // Set value of the 'pipe' property.
        if (!empty($this->returnProperty)) {
            $this
                ->getProject()
                ->setProperty($this->returnProperty, $error_code);
        }

        if (!empty($this->outputProperty)) {
            $this
                ->getProject()
                ->setProperty($this->outputProperty, implode($this->outputGlue, $output));
        }

        // Build fail.
        if ($this->haltOnError && $error_code != 0) {
            throw new BuildException("Drush exited with code $error_code");
        }

        return $error_code != 0;
    }

}

