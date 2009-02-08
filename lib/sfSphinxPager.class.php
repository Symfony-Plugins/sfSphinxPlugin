<?php

class sfSphinxPager extends sfPager
{
  protected
    $peer_method_name       = 'retrieveByPKsJoinAll',
    $peer_count_method_name = 'doCount',
    $keyword = null,
	// Hold a instance of sfSphinxClient
    $sphinx = null;
    //$res = null;
    

  public function __construct($class, $maxPerPage = 10, $sphinx_options = array())
  {
    parent::__construct($class, $maxPerPage);

    $this->tableName = constant($this->getClassPeer().'::TABLE_NAME');
	// default options
    $options = array(
      'limit'   => $maxPerPage,
      'offset'  => 0,
      'mode'    => sfSphinxClient::SPH_MATCH_EXTENDED,
      'weights' => array(100, 1, 10), // FIXME: change the weight
      'sort'    => sfSphinxClient::SPH_SORT_EXTENDED,
      'sortby'  => '@weight DESC',
    );
	$options = array_merge($options, $sphinx_options);
    $this->sphinx = new sfSphinxClient($options);
  }
 
 /**
   * A function to be called after parameters have been set
   */
  public function init()
  {   
    $hasMaxRecordLimit = ($this->getMaxRecordLimit() !== false);
    $maxRecordLimit = $this->getMaxRecordLimit();

    $res = $this->sphinx->Query($this->keyword, $this->tableName);
    $count = $res['total_found'];

    $this->setNbResults($hasMaxRecordLimit ? min($count, $maxRecordLimit) : $count);

    if (($this->getPage() == 0 || $this->getMaxPerPage() == 0))
    {
      $this->setLastPage(0);
    }
    else
    {
      $this->setLastPage(ceil($this->getNbResults() / $this->getMaxPerPage()));

      $offset = ($this->getPage() - 1) * $this->getMaxPerPage();

      if ($hasMaxRecordLimit)
      {
        $maxRecordLimit = $maxRecordLimit - $offset;
        if ($maxRecordLimit > $this->getMaxPerPage())
        {
          $limit = $this->getMaxPerPage();
        }
        else
        {
          $limit = $maxRecordLimit;
        }
      }
      else
      {
        $limit= $this->getMaxPerPage();
      }
      $this->sphinx->SetLimits($offset, $limit);
    }
  }

 /**
   * Retrieve an object of a certain model with offset
   * used internally by getCurrent()
   * @param integer $offset
   */
  protected function retrieveObject($offset)
  {
    $this->sphinx->SetLimits($offset - 1, 1); // We only need one object
    
    $res = $this->sphinx->Query($this->keyword, $this->tableName);
    if ($res['total_found'])
    {
      $ids = array();
      foreach ($res['matches'] as $match)
      {
        $ids[] = $match['id'];
      }
  
      $results = call_user_func(array($this->getClassPeer(), $this->getPeerMethod()), $ids);
      return is_array($results) && isset($results[0]) ? $results[0] : null;
    }
    else
    {
      return null;
    }
  }

 /**
   * returns an array of result on the given page
   */
  public function getResults()
  {
    $res = $this->sphinx->Query($this->keyword, $this->tableName);
    if ($res['total_found'])
    {
	  // First we need to get the Ids
      $ids = array();
      foreach ($res['matches'] as $match)
      {
        $ids[] = $match['id'];
      }
      // Then we retrieve the objects correspoding to the found Ids
      return call_user_func(array($this->getClassPeer(), $this->getPeerMethod()), $ids);
    }
    else
    {
      return array();
    }
    
  }

 /**
   * Returns the peer method name.
   */
  public function getPeerMethod()
  {
    return $this->peer_method_name;
  }

 /**
   * Set the peer method name.
   */
  public function setPeerMethod($peer_method_name)
  {
    $this->peer_method_name = $peer_method_name;
  }

 /**
   * Returns the peer count method name. Default is 'doCount'
   */
  public function getPeerCountMethod()
  {
    return $this->peer_count_method_name;
  }

 /**
   * Set the peer count method name.
   */
  public function setPeerCountMethod($peer_count_method_name)
  {
    $this->peer_count_method_name = $peer_count_method_name;
  }

 /**
   * Returns the current class peer.
   */
  public function getClassPeer()
  {
    return constant($this->class.'::PEER');
  }

 /**
   * Set keyword.
   */  
  public function setKeyword($k)
  {
    $this->keyword = $k;
  }
  
  /**
   * A proxy for Sphinx::SetSortMode()
   * set sort mode
   * @param integer $mode
   * @param string  $sortby
   */
  public function setSortMode($mode, $sortby = '')
  {
    $this->sphinx->SetSortMode($mode, $sortby);
  }
  
  /**
   * A proxy for Sphinx::SetFilter()
   * set values set filter
   * only match records where $attribute value is in given set
   * @param string  $attribute
   * @param array   $values
   * @param boolean $exclude
   */
  public function setFilter($attribute, $values, $exclude = false)
  {
    $this->sphinx->SetFilter($attribute, $values, $exclude);
  }
  
  /**
   * set range filter
   * only match those records where $attribute column value is beetwen $min and $max
   * (including $min and $max)
   * @param string  $attribute
   * @param integer $min
   * @param integer $max
   * @param boolean $exclude
   */
  public function setFilterRange($attribute, $min, $max, $exclude = false)
  {
    $this->sphinx->SetFilterRange($attribute, $min, $max, $exclude);
  }
  
}
