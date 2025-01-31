<?php
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
ini_set("memory_limit", "4096M");

interface ICommonBehaviour{
}

class ListNode implements ICommonBehaviour{
    /*
    * @var ListNode $prev
    *
    * @var ListNode $next
    *
    * @var ListNode $rand
    *
    * @var string $data
    */
    public $prev;
    public $next;
    public $rand; // произвольный элемент внутри списка
    public $data;

    private $_instanceId  = null;
    protected static $instances = 0;

    public function getInstanceId()
    {
        return get_class($this).'_'.$this->_instanceId;
    }
    public function __construct()
    {
        $this->_instanceId = ++self::$instances;
    }
}

class ListNodeDerived extends ListNode{

}
class ListRand implements ICommonBehaviour{
   /*
   * @var ListNode $head
   *
   * @var ListNode $tail
   *
   * @var int $count
   *
   * @var array of ListNode's $nodes
   */
    public $head;
    public $tail;
    public $count;

    protected static function get_deserialized_value($type, $value){
        switch ($type){
            case 'object':
                return new $value;
            case 'NULL':
                return null;
            default:
                settype($value, $type);
                return $value;
        }
    }

    public static function deserialize(ISerializeStrategy $strategy, String $input){
        $arr = $strategy->read($input);
        $nodes = $arr['nodes'];
        $attribute = $arr['attributes'];

        $result = new $arr['class_name'];
        $id = $attribute['head']['value'];
        $result->head = self::get_deserialized_value($attribute['head']['type'], $nodes[$id]['class_name']);
        $result->count = self::get_deserialized_value($attribute['count']['type'], $attribute['count']['value']);
        $promise = '';
        $pointer = $result->head;

        while($pointer){
            $attributes = $nodes[$id]['attributes'];
            $next_id = $attributes['next']['value'];
            $rand_id = $attributes['rand']['value'];

            $pointer->data = self::get_deserialized_value($attributes['data']['type'], $attributes['data']['value']);

            if(isset($next_id)){
                $pointer->next = self::get_deserialized_value($attributes['next']['type'], $nodes[$next_id]['class_name']);
                $pointer->next->prev = $pointer;
            } else {
                $result->tail = $pointer;
            }

            $promise .= isset($rand_id)
                        ?'$result->head'
                            .str_repeat('->next', $nodes[$id]['counter'])
                            .'->rand = $result->head'
                            .str_repeat('->next', $nodes[$rand_id]['counter'])
                            .';'
                        :'';

            $id = $next_id;
            $pointer = $pointer->next;

        }

        eval($promise);
        return $result;
    }

    protected static function get_serialized_value($var_name, $var_value){
        $type = gettype($var_value);
        switch ($type){
            case 'object':
                $val = $var_value->getInstanceId();
                break;
            default:
                $val = $var_value;
        }
        return array('type' => $type, 'value' => $val);
    }

    protected static function serialize_alg(ICommonBehaviour $instance){
        $attributes = array();

        $vars = get_object_vars($instance);
        foreach($vars as $var_name => $var_value){
            $attributes[$var_name] = self::get_serialized_value($var_name, $var_value);
        }

        return array('class_name' => get_class($instance), 'attributes' => $attributes);
    }

    public function serialize(ISerializeStrategy $strategy){
        $hash = 1;
        $arr = array();

        $arr[$hash] = self::serialize_alg($this);

        $nodes = array();
        $node = $this->head;
        $cnt = -1;
        while (is_object($node)){
            $nodes[$node->getInstanceId()] =  self::serialize_alg($node)+array('counter' => $cnt++);
            $node = $node->next;
        }
        $arr[$hash]['nodes'] = $nodes;

        return $strategy->write($arr);
    }
}

class ListRandDerived extends ListRand{

}

interface ISerializeStrategy{
    public function write(array $array);
    public function read(string $string);

}

class FileSerializeStrategy implements ISerializeStrategy{
    public function write(array $array){
        $dir = __DIR__;
        $file_name = 'file.txt';
        $local_file = $dir . DIRECTORY_SEPARATOR . $file_name;
        if (!$fp = fopen($local_file, 'w')) {
             echo "Не могу открыть файл ($local_file)";
             exit;
        }
        fwrite($fp, '<?php return ' . var_export($array, true) . '; ?>');
        fclose($fp);
        return $local_file;
    }

    public function read(string $file){
        if (!is_file($file)) {
             echo "Не могу открыть файл ($file)";
             exit;
        }
        $array = include($file);
        # unlink($file);
        return reset($array);

    }
}

class JSONSerializeStrategy implements ISerializeStrategy{
    public function write(array $array){
        return json_encode($array);
    }

    public function read(string $json_string){
        $js = json_decode($json_string, true);
        return reset($js);
    }
}

# Контрольный пример
$arr = array(
    1 => array(
        'class_name' => 'ListRandDerived',
        'attributes' => array(
            'head' => array(
                'type' => 'object',
                'value' => 1,
                ),
            'tail' => array(
                'type' => 'object',
                'value' => 5,
                ),
            'count' => array(
                'type' => 'integer',
                'value' => 5,
                ),
            ),
        'nodes' => array(
            1 => array(
                'class_name' => 'ListNode',
                'attributes' => array(
                    'prev' => array(
                        'type' => 'NULL',
                        'value' => null,
                        ),
                    'next' => array(
                        'type' => 'object',
                        'value' => 2,
                        ),
                    'rand' => array(
                        'type' => 'NULL',
                        'value' => null,
                        ),
                    'data' => array(
                        'type' => 'boolean',
                        'value' => true,
                        ),
                    ),
                'counter' => 0,
                ),
            2 => array(
                'class_name' => 'ListNodeDerived',
                'attributes' => array(
                    'prev' => array(
                        'type' => 'object',
                        'value' => 1,
                        ),
                    'next' => array(
                        'type' => 'object',
                        'value' => 3,
                        ),
                    'rand' => array(
                        'type' => 'object',
                        'value' => 2,
                        ),
                    'data' => array(
                        'type' => 'string',
                        'value' => 'test_string',
                        ),
                    ),
                'counter' => 1,
                ),
            3 => array(
                'class_name' => 'ListNode',
                'attributes' => array(
                    'prev' => array(
                        'type' => 'object',
                        'value' => 2,
                        ),
                    'next' => array(
                        'type' => 'object',
                        'value' => 4,
                        ),
                    'rand' => array(
                        'type' => 'object',
                        'value' => 5,
                        ),
                    'data' => array(
                        'type' => 'integer',
                        'value' => 777,
                        ),
                    ),
                'counter' => 2,
                ),
            4 => array(
                'class_name' => 'ListNodeDerived',
                'attributes' => array(
                    'prev' => array(
                        'type' => 'object',
                        'value' => 3,
                        ),
                    'next' => array(
                        'type' => 'object',
                        'value' => 5,
                        ),
                    'rand' => array(
                        'type' => 'object',
                        'value' => 1,
                        ),
                    'data' => array(
                        'type' => 'float',
                        'value' => 3.14,
                        ),
                    ),
                'counter' => 3,
                ),
            5 => array(
                'class_name' => 'ListNode',
                'attributes' => array(
                    'prev' => array(
                        'type' => 'object',
                        'value' => 3,
                        ),
                    'next' => array(
                        'type' => 'NULL',
                        'value' => null,
                        ),
                    'rand' => array(
                        'type' => 'NULL',
                        'value' => null,
                        ),
                    'data' => array(
                        'type' => 'array',
                        'value' => array('test'=>1, 2, array()),
                        ),
                    ),
                'counter' => 4,
                ),
            ),

    ),
);
# Пример использования
$file_class = new FileSerializeStrategy;
$input = $file_class->write($arr);
$instance = ListRand::deserialize($file_class, $input);
$file = $instance->serialize($file_class);

$js_class = new JSONSerializeStrategy;
$json_input = $js_class->write($arr);
$instance2 = ListRand::deserialize($js_class, $json_input);
$json_result = $instance2->serialize($js_class);



# # Нагрузочное тестирование
# echo '<table>';
# # for($j=5000; $j<=300000; $j=$j+5000){
# $test_arr = array();
# $test_arr[1]['class_name'] = 'ListRand';
# $test_arr[1]['attributes'] = array(
#     'head' => array('type' => 'object', 'value' => 1),
#     'tail' => array('type' => 'object', 'value' => 1),
#     'count' => array('type' => 'NULL', 'value' => null)
#     );
# $test_arr[1]['nodes'][1] = array(
#     'class_name' => 'ListNode',
#     'attributes' => array(
#         'prev' => array('type' => 'NULL', 'value' => null),
#         'next' => array('type' => 'NULL', 'value' => null),
#         'rand' => array('type' => 'NULL', 'value' => null),
#         'data' => array('type' => 'NULL', 'value' => null),
#         ),
# );
# $file_class = new FileSerializeStrategy;
#
# for ($i=2;$i<=100;$i++){
#     # Increase tail
#     $test_arr[1]['attributes']['tail'] = array('type' => 'object', 'value' => $i);
#     $test_arr[1]['nodes'][$i-1]['attributes']['next'] = array('type' => 'object', 'value' => $i);
#
#     $test_arr[1]['nodes'][$i] = array(
#         'class_name' => 'ListNode',
#         'attributes' => array(
#             'prev' => array('type' => 'object', 'value' => $i-1),
#             'next' => array('type' => 'NULL', 'value' => null),
#             'rand' => array('type' => 'NULL', 'value' => null),
#             'data' => array('type' => 'NULL', 'value' => null),
#             ),
#     );
#
#     $input = $file_class->write($test_arr);
#
#     $start = microtime(true);
#     $instance = ListRand::deserialize($file_class, $input);
#     $curr_unser = microtime(true) - $start;
#     echo '<tr><td>'.$i.'</td><td>', str_replace('.', ',', ($curr_unser)*1000), '</td>';
#     unset($input);
#
#     $start = microtime(true);
#     $output = $instance->serialize($file_class);
#     $curr_ser = microtime(true) - $start;
#     echo '<td>', str_replace('.', ',', ($curr_ser)*1000), '</td></tr>';
#     unset($output);
#     unset($instance);
# }
# echo '</table>';
# die();

?>
