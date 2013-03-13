<?php
visit_limit();

/*****************************************************************************
    程序作者：飞豹游侠    QQ：8527385 9031422   E-mail:liuchengcn@163.com
    使用技术: PHP,Mysql,ADODB,Smarty,PEAR
    本页时间：创建时间:2005-3-18  最后修改时间:2005-4-19
    飞豹网络 http://www.feibao.net
*****************************************************************************/
/* 名称: 对分类操作的业务逻辑封装
  *
  * 作者: 帅的像人渣  QQ: 1191391   E-mail: netcat2@21cn.com
  *
  * 完成日期: 2003-12-18 13:33
  *
  * 说明: 本类中引用的其它类（DB、Table、Item)均未提供，所以本类只能做个参考，不能直接应用
  *       不是本人小气不提供其它类，实在是因为那些都是一两年前写的类，很烂。怕大家看后对大
  *       造成误导. 在此发表这个类，只希望大家能从中学到一些程序设计的方法。
  *       授人以鱼不如授人以渔~
  * 
  * 特点：
  *       采用递归调用的方法，对分类数据只需一次数据库查询可生成树状结构。 无限递归层次(视机器堆栈而定)
  *       
  * 数据库定义： 
  *             category_id          smallint unsigned  primary    #如果数据量很大可用int
  *             parent_id    smallint unsigned  index      #如果数据量很大可用int, 请索引此字段
  *                                                       #如果为根分类，则ParentID = 0
  *                                                       
  *             root_id      smallint unsigned  index      #如果数据量很大可用int, 请索引此字段
  *                                                       #如果是根分类则RootID = 0, 否则RootID = 最上层的父分类ID
  *             category_name varchar(n)                   #此大小自定
  *             如需有其它字段定义附在后面
  
  * 注意事项：
  *           不要试图直接调用本类，除非你有和我定义那另外那几个类相对应的接口, 否则不会成功
  *           在合适的地方定义 DBTABLE_CATEGORY 这个常量，使其指向你的分类数据表名字
  *
  * 程序构架：
  *              ├─基础类   <!-- 完成底层数据库操作、数据抽象、语言、模板、异常、杂项等)操作 -->
  *                 │  
  *                 │  
  *                 └─业务逻辑层（此类所处层次）   <!-- 利用基础类中数据操作、数据抽象等类根据表现层传递的参数完成数据处理，并返回数据或操作结果 -->
  *                     │
  *                     │
  *                     └───表现层（用户界面）   <!-- 利用业务逻辑层将取得的数据或操作数据的结果通过基础类中的界面等类进行显示 -->
  */
  
 
class LeapTree
{    
    var $_CurrentItem   = NULL;             //包含当前分类数据 TItem类的实例
    
    var $category_id     = 0;                //当前分类ID，如果没有当前分类此项为 0
    
    //---------------------------------------------------------------------------
    //private   array getNodeData(array $data, int $ParentNode)
    //          根据一颗指定根的并且以兄弟双亲法表示的树和当前分类的ID，返回当前分类在整个分类表中所处的位置
    //
    // @param: $data          2维数组  Array(
    //                                          Array(
    //                                                  'category_id'            => 分类ID,
    //                                                  'parent_id'      => 父分类ID,
    //                                                  'root_id'        => 根分类ID,
    //                                                  'category_name'  => 分类名称,
    //                                               ),
    //                                          ……
    //                                      );
    //                        表示的一颗树
    //
    // @param: $ParentNode    父分类ID， 每一次由调用者给出，递归时由程序计算传递
    //                        
    // return value:          返回以兄弟双亲法表示的所有分类的树
    //                        注意： 确保当前分类已经设置，否则此函数无返回
    //                        
    //---------------------------------------------------------------------------
    function getNodeData($data, $ParentNode = 0)
    {
        $arr         = array();        
        $array_count = 0;

        $data_nums   = count($data);
        
        for($i = 0; $i < $data_nums; $i++) {
            if ($data[$i]['parent_id'] == $ParentNode) {
                $arr[$array_count]               =   $data[$i];
                $arr[$array_count++]['child']    =   $this->getNodeData($data, $data[$i]['category_id']);
            }
        }

        return $arr;
    }
    /*
    function getNodeData($data, $root_id = 0)
    {
        $output = Array();
        $i      = 0;
        $len    = count($data);

        if($root_id) {
            while($data[$i]['parent_id'] != $root_id && $i < $len) {
                $i++;
            }
        }

        $up_id = $root_id;        //上个节点指向的分类父ID

        for($cnt = count($data); $i < $cnt;) {  //历遍整个分类数组
        
            $j = 0;        //初始化此次分类下子分类数据计数

            if ($up_id == $root_id) {   //在第一次循环时将所有一级分类保存到$Output这个数组中
            
                while($data[$i]['parent_id'] == $up_id && $i < $len) {   //判断上一个节点是否为兄弟节点
                
                    $output[$j] = $data[$i];                //保存该节点到Output这个数组中

                    $tmp[$data[$i]['category_id']] = &$output[$j];    //并且将该节点ID在Output中的位置
                                                            //保存起来.
                    $i++;
                    $j++;
                }
            } else {            
                while($data[$i]['parent_id'] == $up_id && $i < $len) {
                    if($tmp[$up_id]) {
                        $tmp[$up_id]['child'][$j] = $data[$i];
                        $tmp[$data[$i]['category_id']] = &$tmp[$up_id]['child'][$j];    //保存该节点ID在Output中的位置
                    }

                    $i++;
                    $j++;
                }
            }
            echo $i;

            $up_id = $data[$i]['parent_id'];
        }

        return $output;
    }

*/



    //---------------------------------------------------------------------------
    //private   String _currentLevel(array $Data, int $Current, String $process_func = '')
    //          根据一颗指定根的并且以兄弟双亲法表示的树和当前分类的ID，返回当前分类在整个分类表中所处的位置
    //
    // @param: $Data          兄弟双亲法表示的树, 由调用者传递
    //
    // @param: $Current       当前分类ID，第一次调用时由调用者给出，递归时由程序自行计算
    //                        
    // @param: $process_func   指定对分类数据的处理函数, 函数原型定义见 $this->PrintCurrentLevel 中的注释
    //                        
    // return value:          返回当前分类在分类树中的位置
    //                        注意： 确保当前分类已经设置，否则此函数无返回
    //                        
    //---------------------------------------------------------------------------
    function _currentLevel($data, $current, $process_func = '')
    {
        for($i = 0; $i < count($data); $i++) {
            if($data[$i]['category_id'] == $current) {
                if($data[$i]['parent_id'] != 0) {
                    $str = $this->_currentLevel($data, $data[$i]['parent_id'], $process_func) . ' -&gt; ';
                    
                    if($process_func) $str .= $process_func($data[$i]);
                    else $str .= $data[$i]['category_name'];
                } else {
                    if($process_func) $str = $process_func($data[$i]);
                    else $str = $data[$i]['category_name'];
                }
                break;
            }
        }        
        return $str;
    }
    
    //---------------------------------------------------------------------------
    //public   CategoryLogic(Object &$Kernel, int $category_id = -1)
    //         本类构造函数
    //
    // @param: $Kernel        此参数为当前系统核心类的一个引用， 核心类中包括
    //                        数据库类、输入输出类、系统配置类等
    //
    // @param: $category_id    当前分类ID。
    //                        当想调用 printCurrentLevel、GetRootID、GetParentID、generateTypeTreeList及
    //                        调用_CurrentItem成员的方法时请先设置此值.
    //                        
    //                        调用generateTypeTreeList时设置此值，则没有ID为此的分类默认被选择，没设置则无默认
    //
    // return value:          none
    //                        
    //---------------------------------------------------------------------------
    function CategoryLogic($category_id = -1)
    {
        //$this->KernelRef = &$Kernel;

        //$this->tblObj = new Table($Kernel->DBObj, DBTABLE_CATEGORY);

        if ($category_id != -1) {
            $this->setCategoryID($category_id);
        }
    }
    
    //---------------------------------------------------------------------------
    //public   void setCategoryID(int $category_id)
    //              设置当前分类ID
    //
    // return value: none
    //
    //---------------------------------------------------------------------------
    function setCategoryID($category_id)
    {
        if (!$category_id) return;
        
        //$Item = new TItem($this->KernelRef->DBObj, DBTABLE_CATEGORY, '*', $category_id ,'category_id');
        
        //$this->_SelfData = &$Item;
        
        $this->category_id = $category_id;
    }

        
    function getSelfCategoryName($data, $category_id)
    {
        $arr = array();      
        for($i = 0; $i < count($data); $i++) {
            $arr[$data[$i]['category_id']] = $data[$i]['category_name'];
        }
        if (isset($arr[$category_id])) {
            return $arr[$category_id];
        } else {
            return '';
        }
    }
    
    //---------------------------------------------------------------------------
    //public   int GetParentID()
    //             返回当前分类的父分类ID
    //             注意：只有设置的当前分类时此函数才有效
    //
    // return value:  返回当前分类的父分类ID
    //
    //---------------------------------------------------------------------------
    function getParentID(&$data)
    {
        for($i = 0; $i < count($data); $i++) {
            if ($data[$i]['category_id'] == $this->category_id) {
                 return $data[$i]['parent_id'];
            }
        }
        return '';
    }
    function getCategoryName(&$data)
    {
        for($i = 0; $i < count($data); $i++) {
            if ($data[$i]['category_id'] == $this->category_id) {
                 return $data[$i]['category_name'];
            }
        }
        return '';
    }
    
    //---------------------------------------------------------------------------
    //public   String generateTypeTreeList(array $data, String $process_func = '', int $floor = 0)
    //                返回整个分类的树状结构放在OptionList中的列表
    //
    // @param: $data          此参数由 $this->DumpTypeDataToTree() 返回
    //
    // @param: $process_func   处理显示分类信息的回调函数, 函数原型请参照： $this->printCurrentLevel()
    //
    // @param: $floor         本参数不能人为给出，是程序自动计算的中间值
    //
    // return value:          返回一个<option>分类名称1</option> ... <option>分类名称n</option>
    //                        
    // ps: 调用时echo "<select name='xxxx'>" . $_c->generateTypeTreeList($data, 'process_func') . "</select>";
    //
    //---------------------------------------------------------------------------
    function generateTypeTreeList($data, $force_leaf = true, $floor = 0)
    {
        $str = '';
        $cnt = count($data);
        for($i = 0; $i < $cnt; $i++) {
            if ($this->category_id == $data[$i]['category_id']) {
                $str .= '<option value="' . $data[$i]['category_id'] . '" selected>' 
                            . str_repeat("&nbsp;", $floor * 3) 
                            . '├' 
                            . $data[$i]['category_name']
                            . "</option>\r\n";
            } else {
                if ($force_leaf) {
                    if (isset($data[$i]['child']) && count($data[$i]['child']) > 0) {
                        $cid = '';
                    } else {
                        $cid = $data[$i]['category_id'];
                    }
                } else {
                    $cid = $data[$i]['category_id'];
                }
                $str .= '<option value="' . $cid . '">' 
                            . str_repeat("&nbsp;", $floor * 3) 
                            . '├' 
                            . $data[$i]['category_name']
                            . "</option>\r\n";

            }
            
            if (isset($data[$i]['child']) && count($data[$i]['child']) > 0) {
                $str .= $this->generateTypeTreeList($data[$i]['child'], $force_leaf, $floor + 1);
            }
        }
        
        return $str;
    }
    
    //---------------------------------------------------------------------------
    //public   String GenerateTypeTreeView(array $data, String $process_func = '')
    //                返回整个分类的树状结构视图
    //
    // @param: $data          此参数由 $this->DumpTypeDataToTree() 返回
    //
    // @param: $process_func   处理显示分类信息的回调函数, 函数原型请参照： $this->printCurrentLevel()
    //
    // return value:          返回生成的一颗HTML形式显示的树
    //
    //---------------------------------------------------------------------------
    function GenerateTypeTreeView($data, $process_func)
    {
        $Str = '<ul style="Line-Height:200%">';

        for($i = 0, $cnt = count($data); $i < $cnt; $i++)
        {
            if($process_func) $Str .= '<li>' . $process_func($data[$i]) . '</li>' . "\n";
            else $Str .= '<li>' . $data[$i]['category_name'] . '</li>' . "\n";
            
            if($data[$i]['child']) $Str .= '<li>' . $this->GenerateTypeTreeView($data[$i]['child'], $process_func) . '</li>';
        }

        $Str .= '</ul>';
        
        return $Str;
    }
    
    //---------------------------------------------------------------------------
    //public   String PrintCurrentLevel(String $process_func = '')
    //                对多级分类生成当前位置字符串
    //                设如分类数据如下，当前分类为3级分类, 则调用返回    1级分类 -> 2级分类 -> 3级分类
    //                      ├──1级分类
    //                           │  
    //                           │  
    //                           │  
    //                           ├─2级分类
    //                           │ │      
    //                           │ └─3级分类
    //                           │      
    //                           └─2级分类
    //
    //         
    //
    //
    // @param: $process_func   此为对分类数据如何显示的回调函数，不设置则直接显示分类名称
    //                        函数定义原型为   function (&$arr);
    //                        其中$arr参数为每一个分类信息的一维数组如下： 
    //                        array(category_id => 1, ParentID => 0, RootID => 0, CategoryName => '1级分类')
    //                        返回值为对上述数据处理的结果，比如返回带链接的分类名字、更改显示颜色等
    //
    // return value: 返回当前分类在整个分类树中所处位置
    //
    //---------------------------------------------------------------------------
    function printCurrentLevel($data, $process_func = '')
    {
        if(!$this->category_id) return '';
        
        /*
        if($this->_SelfData->Get("RootID") == 0)
        {
            if($process_func) return $process_func($this->_SelfData->fetchDataToArray());
            else return $this->_SelfData->Get("CategoryName");
        }
        */
        
        $current = $this->category_id;

        //$this->tblObj->SetCondition('RootID = ' . $this->_SelfData->Get('RootID') . " or category_id = " . $this->_SelfData->Get('RootID'));
        
        //$Data = $this->tblObj->MapResult($this->tblObj->Select());
        
        return $this->_currentLevel($data, $current, $process_func);
    }


    function _getAllChildID($data, $category_id = 0)
    {
        $category_id_array = '';
        //getNodeData($data);
        $data_ary = $this->getNodeData($data, $category_id);
        for($i = 0, $cnt = count($data_ary); $i < $cnt; $i++) {
            $category_id_array .= ',' . $data_ary[$i]['category_id'];
            if (isset($data_ary[$i]['child']) && count($data_ary[$i]['child']) > 0) {
                $category_id_array .= $this->_getAllChildID($data, $data_ary[$i]['category_id']);
            }
        }
        return $category_id_array;
    }
    function getAllChildID($data, $category_id = 0)
    {
        $ary = $this->_getAllChildID($data, $category_id);
        return $category_id . $ary;
    }
}

?>