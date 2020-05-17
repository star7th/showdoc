<?php

namespace OSS\Model;


/**
 * Class LifecycleRule
 * @package OSS\Model
 *
 * @link http://help.aliyun.com/document_detail/oss/api-reference/bucket/PutBucketLifecycle.html
 */
class LifecycleRule
{
    /**
     * Get Id
     *
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id Rule Id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get a file prefix
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Set a file prefix
     *
     * @param string $prefix The file prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Get Lifecycle status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set Lifecycle status
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     *
     * @return LifecycleAction[]
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @param LifecycleAction[] $actions
     */
    public function setActions($actions)
    {
        $this->actions = $actions;
    }


    /**
     * LifecycleRule constructor.
     *
     * @param string $id rule Id
     * @param string $prefix File prefix
     * @param string $status Rule status, which has the following valid values: [self::LIFECYCLE_STATUS_ENABLED, self::LIFECYCLE_STATUS_DISABLED]
     * @param LifecycleAction[] $actions
     */
    public function __construct($id, $prefix, $status, $actions)
    {
        $this->id = $id;
        $this->prefix = $prefix;
        $this->status = $status;
        $this->actions = $actions;
    }

    /**
     * @param \SimpleXMLElement $xmlRule
     */
    public function appendToXml(&$xmlRule)
    {
        $xmlRule->addChild('ID', $this->id);
        $xmlRule->addChild('Prefix', $this->prefix);
        $xmlRule->addChild('Status', $this->status);
        foreach ($this->actions as $action) {
            $action->appendToXml($xmlRule);
        }
    }

    private $id;
    private $prefix;
    private $status;
    private $actions = array();

    const LIFECYCLE_STATUS_ENABLED = 'Enabled';
    const LIFECYCLE_STATUS_DISABLED = 'Disabled';
}