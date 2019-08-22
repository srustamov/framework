<?php namespace TT\Libraries;

/**
 * @package    TT
 * @author  Samir Rustamov <rustemovv96@gmail.com>
 * @link https://github.com/srustamov/TT
 * @subpackage    Library
 * @category    Validator
 */


use TT\Facades\DB;
use TT\Engine\App;

class Validator
{
    protected $translator;


    protected $fields = [
        'email' => 'is_mail',
        'integer' => 'is_integer',
        'numeric' => 'is_numeric',
        'ip' => 'is_ip',
        'file' => 'is_file',
        'image' => 'is_image'
    ];

    protected $sub_fields = [
        'max','min','unique','regex','confirm'
    ];


    protected $messages  = [];


    protected $custom_messages  = [];


    protected $valid = true;


    public function __construct()
    {
        $this->translator = App::get('language')->get('validator');
    }


    /**
     * @param array $data
     * @param array $rules
     * @return Validator
     */
    public function make(array $data, array $rules):Validator
    {
        $this->messages = [];

        $this->valid    = true;

        foreach ($rules as $key => $value) {
            $fields = explode('|', $value);

            if (isset($data[$key]) && $data[$key] && !empty(trim($data[$key]))) {
                foreach ($fields as $field) {
                    if (($position = array_search($field, array_keys($this->fields), true)) !== false) {
                        $function = array_values($this->fields)[$position];

                        if (!$this->$function($data[$key])) {
                            $this->translation($field, $key, ['field' => $key]);
                        }
                    } else {
                        $parts = explode(':', $field, 2);

                        if ((count($parts) > 1) && ($position = array_search($parts[0], $this->sub_fields, true)) !== false) {
                            $function = $this->sub_fields[$position];

                            if ($function !== 'unique') {
                                if (!$this->$function($data[$key], $parts[1])) {
                                    $this->translation($function, $key, ['field' => $key,$function => $parts[1]]);
                                }
                            } else {
                                $control = $control = DB::table($parts[1])->where($key, $data[$key])->first();

                                if ($control) {
                                    $this->translation('unique', $key, ['field' => $key]);
                                }
                            }
                        }
                    }
                }
            } elseif (in_array('required', $fields, true)) {
                $this->translation('required', $key, ['field' => $key]);
            }
        }

        if (!empty($this->messages)) {
            $this->valid = false;
        }

        return $this ;
    }


    public function setMessage($key, $value = null)
    {
        $keys = is_array($key) ? $key : [$key => $value];

        foreach ($keys as $k => $v) {
            Arr::set($this->custom_messages, $k, $v);
        }

        return $this;
    }


    public function setMessages(array $messages)
    {
        foreach ($messages as $key => $value) {
            $this->setMessage($key, $value);
        }

        return $this;
    }


    private function translation($field, $key, $replace = [])
    {
        if ($custom_message = Arr::get($this->custom_messages, $key.'.'.$field)) {
            $this->messages[$key][] = $custom_message;
        } elseif (isset($this->translator['custom_messages']) && ($custom_message = Arr::get($this->translator['custom_messages'], $key.'.'.$field))) {
            $this->messages[$key][] = $custom_message;
        } elseif (isset($this->translator[$field])) {
            if (!empty($replace)) {
                $keys = array_map(
                    static function ($item) {
                        return ':'.$item;
                    },
                    array_keys($replace)
                );

                $this->messages[$key][] = str_replace($keys, array_values($replace), $this->translator[$field]);
            } else {
                $this->messages[$key][] = $this->translator[$field] ?? '';
            }
        }
    }


    public function check():Bool
    {
        return $this->valid;
    }


    public function messages():array
    {
        return $this->messages;
    }


    public function is_mail($email)
    {
        if (is_array($email)) {
            return false;
        }
        return filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    }


    public function is_integer($value): bool
    {
        return is_int($value);
    }


    public function is_numeric($value): bool
    {
        return is_numeric($value);
    }


    public function is_image($value):Bool
    {
        if (isset($value['tmp_name'])) {
            return @getimagesize($value['tmp_name']) ? true : false;
        }

        return @getimagesize($value) ? true : false;
    }


    public function is_file($value):Bool
    {
        if (isset($value['tmp_name'])) {
            return is_file($value['tmp_name']);
        }

        return is_file($value);
    }


    public function is_url($url)
    {
        if (!is_string($url)) {
            return false;
        }
        return filter_var($url, FILTER_VALIDATE_URL);
    }


    public function is_ip($ip)
    {
        if (!is_string($ip)) {
            return false;
        }
        return filter_var($ip, FILTER_VALIDATE_IP);
    }


    public function max($value, $max): bool
    {
        return (mb_strlen($value) <= (int) $max);
    }

    public function min($value, $min): bool
    {
        return (mb_strlen($value) >= (int) $min);
    }

    public function regex($value, $pattern): bool
    {
        return !preg_match("#^$pattern$#", $value);
    }

    public function confirm($value1, $value2): bool
    {
        return ($value1 === $value2);
    }
}
