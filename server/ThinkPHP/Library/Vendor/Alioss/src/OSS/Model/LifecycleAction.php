<?php

namespace OSS\Model;

/**
 * Class LifecycleAction
 * @package OSS\Model
 * @link http://help.aliyun.com/document_detail/oss/api-reference/bucket/PutBucketLifecycle.html
 */
class LifecycleAction
{
    /**
     * LifecycleAction constructor.
     * @param string $action
     * @param string $timeSpec
     * @param string $timeValue
     */
    public function __construct($action, $timeSpec, $timeValue)
    {
        $this->action = $action;
        $this->timeSpec = $timeSpec;
        $this->timeValue = $timeValue;
    }

    /**
     * @return LifecycleAction
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     */
    public function setAction($action)
    {
        $this->action = $action;
    }

    /**
     * @return string
     */
    public function getTimeSpec()
    {
        return $this->timeSpec;
    }

    /**
     * @param string $timeSpec
     */
    public function setTimeSpec($timeSpec)
    {
        $this->timeSpec = $timeSpec;
    }

    /**
     * @return string
     */
    public function getTimeValue()
    {
        return $this->timeValue;
    }

    /**
     * @param string $timeValue
     */
    public function setTimeValue($timeValue)
    {
        $this->timeValue = $timeValue;
    }

    /**
     * Use appendToXml to insert actions into xml.
     *
     * @param \SimpleXMLElement $xmlRule
     */
    public function appendToXml(&$xmlRule)
    {
        $xmlAction = $xmlRule->addChild($this->action);
        $xmlAction->addChild($this->timeSpec, $this->timeValue);
    }

    private $action;
    private $timeSpec;
    private $timeValue;

}