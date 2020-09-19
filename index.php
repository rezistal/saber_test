<?php
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
ini_set("memory_limit", "4096M");

# Контрольный пример
# $arr = array(
#     1 => array(
#         'class_name' => 'ListRandDerived',
#         'attributes' => array(
#             'head' => array(
#                 'type' => 'object',
#                 'value' => 1,
#                 ),
#             'tail' => array(
#                 'type' => 'object',
#                 'value' => 5,
#                 ),
#             'count' => array(
#                 'type' => 'integer',
#                 'value' => 5,
#                 ),
#             ),
#         'nodes' => array(
#             1 => array(
#                 'class_name' => 'ListNode',
#                 'attributes' => array(
#                     'prev' => array(
#                         'type' => 'NULL',
#                         'value' => null,
#                         ),
#                     'next' => array(
#                         'type' => 'object',
#                         'value' => 2,
#                         ),
#                     'rand' => array(
#                         'type' => 'NULL',
#                         'value' => null,
#                         ),
#                     'data' => array(
#                         'type' => 'boolean',
#                         'value' => true,
#                         ),
#                     ),
#                 ),
#             '2' => array(
#                 'class_name' => 'ListNodeDerived',
#                 'attributes' => array(
#                     'prev' => array(
#                         'type' => 'object',
#                         'value' => 1,
#                         ),
#                     'next' => array(
#                         'type' => 'object',
#                         'value' => 3,
#                         ),
#                     'rand' => array(
#                         'type' => 'object',
#                         'value' => 2,
#                         ),
#                     'data' => array(
#                         'type' => 'string',
#                         'value' => 'test_string',
#                         ),
#                     ),
#                 ),
#             3 => array(
#                 'class_name' => 'ListNode',
#                 'attributes' => array(
#                     'prev' => array(
#                         'type' => 'object',
#                         'value' => 2,
#                         ),
#                     'next' => array(
#                         'type' => 'object',
#                         'value' => 4,
#                         ),
#                     'rand' => array(
#                         'type' => 'object',
#                         'value' => 5,
#                         ),
#                     'data' => array(
#                         'type' => 'integer',
#                         'value' => 777,
#                         ),
#                     ),
#                 ),
#             4 => array(
#                 'class_name' => 'ListNodeDerived',
#                 'attributes' => array(
#                     'prev' => array(
#                         'type' => 'object',
#                         'value' => 3,
#                         ),
#                     'next' => array(
#                         'type' => 'object',
#                         'value' => 5,
#                         ),
#                     'rand' => array(
#                         'type' => 'object',
#                         'value' => 1,
#                         ),
#                     'data' => array(
#                         'type' => 'float',
#                         'value' => 3.14,
#                         ),
#                     ),
#                 ),
#             '5' => array(
#                 'class_name' => 'ListNode',
#                 'attributes' => array(
#                     'prev' => array(
#                         'type' => 'object',
#                         'value' => 3,
#                         ),
#                     'next' => array(
#                         'type' => 'NULL',
#                         'value' => null,
#                         ),
#                     'rand' => array(
#                         'type' => 'NULL',
#                         'value' => null,
#                         ),
#                     'data' => array(
#                         'type' => 'array',
#                         'value' => array('test'=>1, 2, array()),
#                         ),
#                     ),
#                 ),
#             ),
#
#     ),
# );
# $input = json_encode($arr);

interface ICommonBehaviour{
    public function getInstanceId();
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
        return $this->_instanceId;
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

    protected static $nodes = array();

    protected $_instanceId  = null;
    protected static $instances = 0;

    public function getInstanceId(){
        return $this->_instanceId;
    }

    public function __construct(){
        $this->_instanceId = ++self::$instances;
    }

    protected static function add_nodes($hash, ListNode $node){
        self::$nodes[$hash] = $node;
    }

    protected static function get_deserialized_value($type, $value){
        switch ($type){
            case 'object':
                return self::$nodes[$value];
            case 'NULL':
                return null;
            default:
                settype($value, $type);
                return $value;
        }
    }

    protected static function deserialize_alg(Array $attributes, ICommonBehaviour $instance){
        foreach($attributes as $attribute => $attribute_properties){
            $type = $attribute_properties['type'];
            $value = $attribute_properties['value'];
            $instance->$attribute = self::get_deserialized_value($type, $value);
        }
    }

    public static function deserialize(String $input){
        $js = json_decode($input, true);
        $js = reset($js);

        $nodes = $js['nodes'];
        foreach($nodes as $hash => $node){
            self::add_nodes($hash, new $node['class_name']);
        }

        foreach(self::$nodes as $hash => $node_instance){
            self::deserialize_alg($nodes[$hash]['attributes'], $node_instance);
        }

        $result = new $js['class_name'];
        self::deserialize_alg($js['attributes'], $result);
        return $result;
    }

    protected static function get_serialized_value($var_value){
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
            $attributes[$var_name] = self::get_serialized_value($var_value);
        }
        # Привет первый костыль. Так как в serialize_alg($this) передается
        #   экземпляр класса то получаем доступ к приватному аттрибуту,
        #   чего хотелось бы избежать
        unset($attributes['_instanceId']);

        return array('class_name' => get_class($instance), 'attributes' => $attributes);
    }

    public function serialize(){
        $hash = $this->getInstanceId();
        $arr = array();

        $arr[$hash] = self::serialize_alg($this);

        $nodes = array();
        $node = $this->head;
        while (is_object($node)){
            $nodes[$node->getInstanceId()] =  self::serialize_alg($node);
            $node = $node->next;
        }
        $arr[$hash]['nodes'] = $nodes;
        return json_encode($arr);
    }

}

class ListRandDerived extends ListRand{

}


$input = '{"1":{"class_name":"ListRandDerived","attributes":{"head":{"type":"object","value":1},"tail":{"type":"object","value":2},"count":{"type":"integer","value":5}},"nodes":{"1":{"class_name":"ListNode","attributes":{"prev":{"type":"NULL","value":null},"next":{"type":"object","value":2},"rand":{"type":"NULL","value":null},"data":{"type":"boolean","value":true}}},"2":{"class_name":"ListNodeDerived","attributes":{"prev":{"type":"object","value":1},"next":{"type":"object","value":3},"rand":{"type":"object","value":2},"data":{"type":"string","value":"test_string"}}},"3":{"class_name":"ListNode","attributes":{"prev":{"type":"object","value":2},"next":{"type":"object","value":4},"rand":{"type":"object","value":5},"data":{"type":"integer","value":777}}},"4":{"class_name":"ListNodeDerived","attributes":{"prev":{"type":"object","value":3},"next":{"type":"object","value":5},"rand":{"type":"object","value":1},"data":{"type":"float","value":3.14}}},"5":{"class_name":"ListNode","attributes":{"prev":{"type":"object","value":3},"next":{"type":"NULL","value":null},"rand":{"type":"NULL","value":null},"data":{"type":"array","value":{"test":1,"0":2,"1":[]}}}}}}}';
$instance = ListRand::deserialize($input);

$output = $instance->serialize();
$instance2 = ListRand::deserialize($output);

echo '<pre>';
# Немного проверок
var_dump($instance->head->next->next->next->next == $instance->tail);
var_dump($instance->head->next->rand == $instance->head->next);
echo '</br></pre>';

?>
