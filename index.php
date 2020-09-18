<?php           
ini_set('display_errors', 1);
ini_set('max_execution_time', 0);
ini_set("memory_limit", "4096M");
# $arr = array(
#     'nodes' => array(
#         '0000000068401f1c000000006f9728c3' => array(
#             'prev' => null,
#             'next' => '0000000068401f1d000000006f9728c3',
#             'rand' => null,
#             'data' => '1',
#             ), 
#         '0000000068401f1d000000006f9728c3' => array(
#             'prev' => '0000000068401f1c000000006f9728c3',
#             'next' => '0000000068401f1a000000006f9728c3',
#             'rand' => '0000000068401f1d000000006f9728c3',
#             'data' => '2',
#             ), 
#         '0000000068401f1a000000006f9728c3' => array(
#             'prev' => '0000000068401f1d000000006f9728c3',
#             'next' => '0000000068401f1b000000006f9728c3',
#             'rand' => '0000000068401f18000000006f9728c3',
#             'data' => '3',
#             ), 
#         '0000000068401f1b000000006f9728c3' => array(
#             'prev' => '0000000068401f1a000000006f9728c3',
#             'next' => '0000000068401f18000000006f9728c3',
#             'rand' => '0000000068401f1c000000006f9728c3',
#             'data' => '4',
#             ), 
#         '0000000068401f18000000006f9728c3' => array(
#             'prev' => '0000000068401f1b000000006f9728c3',
#             'next' => null,
#             'rand' => null,
#             'data' => '5',
#             ),
#         ),
#     'head' => '0000000068401f1c000000006f9728c3',
#     'tail' => '0000000068401f18000000006f9728c3',
#     'count' => 5,
# );
# var_dump(json_encode($arr));
# die();


class ListNode
{
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

class ListRand
{
   /*
   * @var ListNode $head
   * 
   * @var ListNode $tail
   * 
   * @var int $count
   * 
   * @var array of ListNode's $children
   */
    public $head;
    public $tail;
    public $count;
    
    protected $children = array();
        
    protected function add_children($hash, ListNode $node)
    {
        $this->children[$hash] = $node;
    }

    public static function deserialize(String $input)
    {
        $js = json_decode($input, true);
        $result = new ListRand();
        
        $nodes = $js['nodes'];
        $head = $js['head'];
        $tail = $js['tail'];
        $count = $js['count'];

        foreach($nodes as $hash => $node){
            $result->add_children($hash, new ListNode());
        }
        
        foreach($result->children as $hash => $node_instance){
            $key_next = $nodes[$hash]['next'];
            $key_prev = $nodes[$hash]['prev'];
            $key_rand = $nodes[$hash]['rand'];
            if(isset($key_next)){
                $node_instance->next = $result->children[$key_next];
            }
            if(isset($key_prev)){
                $node_instance->prev = $result->children[$key_prev];
            }
            if(isset($key_rand)){
                $node_instance->rand = $result->children[$key_rand];
            }
            $node_instance->data = $nodes[$hash]['data'];
        }

        $result->head = $result->children[$head];
        $result->tail = $result->children[$tail];
        $result->count = $count;
        unset($result->children);
        return $result;

    }
    
    public function serialize()
    {
        $arr = array();
        $arr['count'] = $this->count;
        $arr['head'] = spl_object_hash($this->head);
        $arr['tail'] = spl_object_hash($this->tail);
        $node = $this->head;
        while (isset($node)){
            $hash = spl_object_hash($node);
            $arr['nodes'][$hash]['prev'] = isset($node->prev)?spl_object_hash($node->prev):null;
            $arr['nodes'][$hash]['next'] = isset($node->next)?spl_object_hash($node->next):null;
            $arr['nodes'][$hash]['rand'] = isset($node->rand)?spl_object_hash($node->rand):null;
            $arr['nodes'][$hash]['data'] = $node->data;
            $node = $node->next;
        }
        return json_encode($arr);
    }
}

$input = '{"count":5,"head":"0000000058ed9105000000007fb645e3","tail":"0000000058ed9101000000007fb645e3","nodes":{"0000000058ed9105000000007fb645e3":{"prev":null,"next":"0000000058ed9104000000007fb645e3","rand":null,"data":"1"},"0000000058ed9104000000007fb645e3":{"prev":"0000000058ed9105000000007fb645e3","next":"0000000058ed9103000000007fb645e3","rand":"0000000058ed9104000000007fb645e3","data":"2"},"0000000058ed9103000000007fb645e3":{"prev":"0000000058ed9104000000007fb645e3","next":"0000000058ed9102000000007fb645e3","rand":"0000000058ed9101000000007fb645e3","data":"3"},"0000000058ed9102000000007fb645e3":{"prev":"0000000058ed9103000000007fb645e3","next":"0000000058ed9101000000007fb645e3","rand":"0000000058ed9105000000007fb645e3","data":"4"},"0000000058ed9101000000007fb645e3":{"prev":"0000000058ed9102000000007fb645e3","next":null,"rand":null,"data":"5"}}}';
$instance = ListRand::deserialize($input);

$output = $instance->serialize();
$instance2 = ListRand::deserialize($output);

echo '<pre>';
# Немного проверок
var_dump($instance->head->next->next->next->next == $instance->tail);
var_dump($instance->head->next->rand == $instance->head->next);
echo '</br></pre>';

?>
