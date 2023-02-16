<?php
namespace SegHEIRS\components\his;

/**
 * @todo POST, UPDATE, DELETE
 * 
 * Responsible for setting up the HIS REST connection.
 * Will be used as D.I, so config, will be set in the main.php 
 */
class HISRestConnection
{

    public $url;
    public $company = 0;
    public $userid;
    public $password;
    private $_pest;

    protected function getpest() 
    {
        if(empty($this->_pest))
            $this->_pest = new \Pest($this->url);
        return $this->_pest;
    }

    protected function formatResource($resource, $urlData = array()) 
    {
        return strtr($resource, $urlData); 
    }

    public function get($resource, $urlData = array(), $data = array()) 
    {
        try{
            $result = $this->pest->get($this->formatResource(
                $resource, $urlData
            ), array_merge(array(
                'cmp' => $this->company,
                'uid' => $this->userid,
                'pwd' => $this->password
            ), $data));
            return json_decode($result, true);

        } catch(\Pest_Exception $ex) {


        }
    }

    public function post($resource, $urlData = array(), $data) 
    {
        try{

            $result = $this->pest->post($this->formatResource(
                $resource, $urlData
            ), array_merge(array(
                'cmp' => $this->company,
                'uid' => $this->userid,
                'pwd' => $this->password
            ), $data));
            return json_decode($result, true);

        } catch(\Pest_Exception $ex) {


        }
    }

}