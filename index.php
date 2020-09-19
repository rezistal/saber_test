<?php
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
ini_set("memory_limit", "4096M");

# Контрольный пример
# $arr = array(
#     '0000000023b9940300000000439b296e' => array(
#         'class_name' => 'ListRandDerived',
#         'attributes' => array(
#             'head' => '0000000068401f1c000000006f9728c3',
#             'tail' => '0000000068401f18000000006f9728c3',
#             'count' => 5,
#             ),
#         'nodes' => array(
#             '0000000068401f1c000000006f9728c3' => array(
#                 'class_name' => 'ListNode',
#                 'attributes' => array(
#                     'prev' => null,
#                     'next' => '0000000068401f1d000000006f9728c3',
#                     'rand' => null,
#                     'data' => '1',
#                     ),
#                 ),
#             '0000000068401f1d000000006f9728c3' => array(
#                 'class_name' => 'ListNodeDerived',
#                 'attributes' => array(
#                     'prev' => '0000000068401f1c000000006f9728c3',
#                     'next' => '0000000068401f1a000000006f9728c3',
#                     'rand' => '0000000068401f1d000000006f9728c3',
#                     'data' => '2',
#                     ),
#                 ),
#             '0000000068401f1a000000006f9728c3' => array(
#                 'class_name' => 'ListNode',
#                 'attributes' => array(
#                     'prev' => '0000000068401f1d000000006f9728c3',
#                     'next' => '0000000068401f1b000000006f9728c3',
#                     'rand' => '0000000068401f18000000006f9728c3',
#                     'data' => '3',
#                     ),
#                 ),
#             '0000000068401f1b000000006f9728c3' => array(
#                 'class_name' => 'ListNodeDerived',
#                 'attributes' => array(
#                     'prev' => '0000000068401f1a000000006f9728c3',
#                     'next' => '0000000068401f18000000006f9728c3',
#                     'rand' => '0000000068401f1c000000006f9728c3',
#                     'data' => '4',
#                     ),
#                 ),
#             '0000000068401f18000000006f9728c3' => array(
#                 'class_name' => 'ListNode',
#                 'attributes' => array(
#                     'prev' => '0000000068401f1b000000006f9728c3',
#                     'next' => null,
#                     'rand' => null,
#                     'data' => '5',
#                     ),
#                 ),
#             ),
#
#     ),
# );
# var_dump(json_encode($arr));
# die();

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

    protected static function add_nodes($hash, ListNode $node){
        self::$nodes[$hash] = $node;
    }

    protected static function get_deserialized_value($var_value){
        if(isset($var_value)){
            if(array_key_exists($var_value, self::$nodes) && is_object(self::$nodes[$var_value])){
                return self::$nodes[$var_value];
            }
            return $var_value;
        }
        return null;
    }

    protected static function deserialize_alg(Array $attributes, ICommonBehaviour $instance){
        $vars = get_object_vars($instance);
        foreach($vars as $var_name => $var_value){
            $hash_or_realdata = $attributes[$var_name];
            $instance->$var_name = self::get_deserialized_value($hash_or_realdata);
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
        
        self::$nodes = array();
        return $result;
    }

    protected function get_serialized_value($var_name, $var_value){
        if(is_object($var_value)){
            return spl_object_hash($var_value);
        }
        return $var_value;
    }

    protected function serialize_alg(ICommonBehaviour $instance){
        $attributes = array();
        $vars = get_object_vars($instance);
        foreach($vars as $var_name => $var_value){
            $attributes[$var_name] = $this->get_serialized_value($var_name, $var_value);
        }
        return array('class_name' => get_class($instance), 'attributes' => $attributes);
    }

    public function serialize(){
        $hash = spl_object_hash($this);
        $arr = array();
        $arr[$hash] = $this->serialize_alg($this);

        $nodes = array();
        $node = $this->head;
        while (is_object($node)){
            $nodes[spl_object_hash($node)] = $this->serialize_alg($node);
            $node = $node->next;
        }
        $arr[$hash]['nodes'] = $nodes;
        return json_encode($arr);
    }

}

class ListRandDerived extends ListRand{

}


$input = '{"0000000023b9940300000000439b296e":{"class_name":"ListRandDerived","attributes":{"head":"0000000068401f1c000000006f9728c3","tail":"0000000068401f18000000006f9728c3","count":5},"nodes":{"0000000068401f1c000000006f9728c3":{"class_name":"ListNode","attributes":{"prev":null,"next":"0000000068401f1d000000006f9728c3","rand":null,"data":"1"}},"0000000068401f1d000000006f9728c3":{"class_name":"ListNodeDerived","attributes":{"prev":"0000000068401f1c000000006f9728c3","next":"0000000068401f1a000000006f9728c3","rand":"0000000068401f1d000000006f9728c3","data":"2"}},"0000000068401f1a000000006f9728c3":{"class_name":"ListNode","attributes":{"prev":"0000000068401f1d000000006f9728c3","next":"0000000068401f1b000000006f9728c3","rand":"0000000068401f18000000006f9728c3","data":"3"}},"0000000068401f1b000000006f9728c3":{"class_name":"ListNodeDerived","attributes":{"prev":"0000000068401f1a000000006f9728c3","next":"0000000068401f18000000006f9728c3","rand":"0000000068401f1c000000006f9728c3","data":"4"}},"0000000068401f18000000006f9728c3":{"class_name":"ListNode","attributes":{"prev":"0000000068401f1b000000006f9728c3","next":null,"rand":null,"data":"5"}}}}}';
$instance = ListRand::deserialize($input);

$output = $instance->serialize();
$instance2 = ListRand::deserialize($output);

echo '<pre>';
# Немного проверок
var_dump($instance->head->next->next->next->next == $instance->tail);
var_dump($instance->head->next->rand == $instance->head->next);
echo '</br></pre>';

?>
