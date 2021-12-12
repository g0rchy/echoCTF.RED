<?php
/**
 * Check email address MX Records for denied servers.
 */
namespace app\components\validators;

use yii\validators\Validator;
use yii\helpers\ArrayHelper;

class MXServersValidator extends Validator
{
    public $range=[];
    public $message="Sorry but you are using an email server that is banned!";

    public function init()
    {
        parent::init();
    }

    public function validateValue($value)
    {
      if(!getmxrr($value, $hosts))
      {
        return [$this->message, [
            'domain' => $value,
        ]];
      }

      foreach($this->range as $key)
      {
        if(array_search($key, $hosts)!==false)
          return [$this->message, [
              'domain' => $value,
          ]];
      }
    }

    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if(!getmxrr($value, $hosts))
        {
          $model->addError($attribute, $this->message);
        }

        foreach($this->range as $key)
        {
          if(array_search($key, $hosts)!==false)
            $model->addError($attribute, $this->message);
        }
    }
}
