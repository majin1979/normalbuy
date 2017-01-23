<?
require "sqlsrv_driver.php";
/**
 * 此页面获值方式
 * type=getlist 获取存货的list，模糊查询
 */

$dbinfo = parse_ini_file("building/data_out/dbconfig.ini");
$_db = array( //数据库连接信息
	//主机地址
    'hostname' => $dbinfo["dbhost"],
    //用户名
    'username' => $dbinfo["dbuser"],
    //密码
    'password' => $dbinfo["dbpasswd"],
    //数据库名
    'dbname' => $dbinfo["dbname"],
    //端口  默认1433
    'port' => 1433,
);
//数据库连接
$db = SQLSrv::getdatabase($_db);

include_once("inc/conn.php");
// 上级控件传递的值
 $dataq = $_REQUEST;
//file_put_contents("log/json.txt", "-----------------------------------jsonq-----------------------------------"."\r\n"."\r\n",FILE_APPEND);
//file_put_contents("log/json.txt", json_encode($dataq)."\r\n"."\r\n",FILE_APPEND);
$relparent = $_REQUEST['rel_parent'];
// $relparent = iconv("GBK","UTF-8",$relparent);
// 数据源链接后面拼接的参数，用竖线(|)分割，依次代表：[表名]，[查询目标字段名]，[查询条件字段名]
$tfs      = $_REQUEST['tfs'];
$tfsarray = explode("|",$tfs);
$t        = $tfsarray[0];
$f        = $tfsarray[1];
$s        = $tfsarray[2];
// 数据源链接后面拼接的参数，用来区分取值类别
$type = $_REQUEST["type"];
// 动态联动控件，类别是单行文本框时，输入框输入的内容
$filter = $_REQUEST['filter'];
$filter = str_replace("'","",$filter );
$dataj  = "[\"data error!\"]";
$runId = $_REQUEST["run_id"];

if(!$type){
	echo $dataj;
	exit;
}

if($type == "b_pricing" || $type == "b_vendor" || $type == "b_vendor_tel" || $type == "b_pricing_date") {
//	$history = getHistoryData($runId,$relparent);
	$history = getHistoryData($runId);
}
function getHistoryData($runId) {
	
	//file_put_contents("log/".$runId.".txt", "-----------------------------------获取历史批价记录-----------------------------------\r\n",FILE_APPEND);
	global $connection;
	$listindex = $_REQUEST['listindex'];
	//file_put_contents("log/".$runId.".txt", "listindex = ".$listindex."\r\n",FILE_APPEND);
	$sql = "
	SELECT fr.FLOW_ID,ft.FORM_ID FROM  flow_run fr
	LEFT JOIN flow_type ft ON fr.flow_id = ft.flow_id
	WHERE fr.run_id = '".$runId."'
	";
	
	//file_put_contents("log/".$runId.".txt", $sql."\r\n",FILE_APPEND);
	$rs = exequery($connection,$sql);
	if($row = mysql_fetch_array($rs)) {
		$flowId = $row["FLOW_ID"];
		$formId = $row["FORM_ID"];// 最后的数据
		$sql2 = "
		SELECT * FROM zzzz_flow_data_$formId WHERE run_id = '".$runId."'
		";
		//file_put_contents("log/".$runId.".txt", $sql2."\r\n",FILE_APPEND);
		$res2 = exequery($connection,$sql2);
		if($data2 = mysql_fetch_assoc($res2))
		{
			//file_put_contents("log/".$runId.".txt", "-----------------------------------当前表单-----------------------------------"."\r\n",FILE_APPEND);
			//file_put_contents("log/".$runId.".txt", iconv("UTF-8","GB2312//IGNORE",json_encode($data2))."\r\n",FILE_APPEND);
			$pinming = $data2[$listindex];			
			//file_put_contents("log/".$runId.".txt", "Find list = ".$pinming."\r\n",FILE_APPEND);
			if($pinming!="")
			{
				$sql3 = "
				SELECT *
				FROM zzzz_flow_data_$formId
				WHERE (
				((DATA_8 = '".$pinming."')AND((DATA_69 = 'on')OR(DATA_70 = 'on')))OR
				((DATA_18 = '".$pinming."')AND((DATA_71 = 'on')OR(DATA_72 = 'on')))OR
				((DATA_21 = '".$pinming."')AND((DATA_73 = 'on')OR(DATA_74 = 'on')))OR
				((DATA_24 = '".$pinming."')AND((DATA_75 = 'on')OR(DATA_76 = 'on')))OR
				((DATA_27 = '".$pinming."')AND((DATA_77 = 'on')OR(DATA_78 = 'on')))OR
				((DATA_30 = '".$pinming."')AND((DATA_79 = 'on')OR(DATA_80 = 'on')))
				)AND run_id != '".$runId."'
				ORDER BY run_id DESC limit 0,1";
				//file_put_contents("log/".$runId.".txt", $sql3."\r\n",FILE_APPEND);
				$res3 = exequery($connection,$sql3);
				if($data = mysql_fetch_assoc($res3)) {
					// echo "<pre>";
					// print_r($data);
					//file_put_contents("log/".$runId.".txt", "-----------------------------------历史表单-----------------------------------"."\r\n",FILE_APPEND);
					//file_put_contents("log/".$runId.".txt", iconv("UTF-8","GB2312//IGNORE",json_encode($data))."\r\n",FILE_APPEND);
					$pijia         = "";
					$gongyingshang = "";
					$dianhua       = "";
					if($data["DATA_8"] == $pinming) {
						if($data["DATA_69"] == "on") {
							$pijia = $data["DATA_92"];
							$gongyingshang = $data["DATA_93"];
							$dianhua = $data["DATA_86"];
							//file_put_contents("log/".$runId.".txt", "第一行第一项\r\n",FILE_APPEND);
						} else if($data["DATA_70"] == "on") {
							$pijia = $data["DATA_92"];
							$gongyingshang = $data["DATA_93"];
							$dianhua = $data["DATA_91"];
							//file_put_contents("log/".$runId.".txt", "第一行第二项\r\n",FILE_APPEND);
						}
					}else if($data["DATA_18"] == $pinming) {
						if($data["DATA_71"] == "on") {
							$pijia = $data["DATA_102"];
							$gongyingshang = $data["DATA_103"];
							$dianhua = $data["DATA_96"];
							//file_put_contents("log/".$runId.".txt", "第二行第一项\r\n",FILE_APPEND);
						} else if($data["DATA_72"] == "on") {
							$pijia = $data["DATA_102"];
							$gongyingshang = $data["DATA_103"];
							$dianhua = $data["DATA_101"];
							//file_put_contents("log/".$runId.".txt", "第二行第二项\r\n",FILE_APPEND);
						}
					}else if($data["DATA_21"] == $pinming) {
						if($data["DATA_73"] == "on") {
							$pijia = $data["DATA_112"];
							$gongyingshang = $data["DATA_113"];
							$dianhua = $data["DATA_106"];
							//file_put_contents("log/".$runId.".txt", "第三行第一项\r\n",FILE_APPEND);
						} else if($data["DATA_74"] == "on") {
							$pijia = $data["DATA_112"];
							$gongyingshang = $data["DATA_113"];
							$dianhua = $data["DATA_111"];
							//file_put_contents("log/".$runId.".txt", "第三行第二项\r\n",FILE_APPEND);
						}
					}else if($data["DATA_24"] == $pinming) {
						if($data["DATA_75"] == "on") {
							$pijia = $data["DATA_122"];
							$gongyingshang = $data["DATA_123"];
							$dianhua = $data["DATA_116"];
							//file_put_contents("log/".$runId.".txt", "第四行第一项\r\n",FILE_APPEND);
						} else if($data["DATA_76"] == "on") {
							$pijia = $data["DATA_122"];
							$gongyingshang = $data["DATA_123"];
							$dianhua = $data["DATA_121"];
							//file_put_contents("log/".$runId.".txt", "第四行第二项\r\n",FILE_APPEND);
						}
					}else if($data["DATA_27"] == $pinming) {
						if($data["DATA_77"] == "on") {
							$pijia = $data["DATA_132"];
							$gongyingshang = $data["DATA_133"];
							$dianhua = $data["DATA_126"];
							//file_put_contents("log/".$runId.".txt", "第五行第一项\r\n",FILE_APPEND);
						} else if($data["DATA_78"] == "on") {
							$pijia = $data["DATA_132"];
							$gongyingshang = $data["DATA_133"];
							$dianhua = $data["DATA_131"];
							//file_put_contents("log/".$runId.".txt", "第五行第二项\r\n",FILE_APPEND);
						}
					}else if($data["DATA_30"] == $pinming) {
						if($data["DATA_79"] == "on") {
							$pijia = $data["DATA_142"];
							$gongyingshang = $data["DATA_143"];
							$dianhua = $data["DATA_136"];
							//file_put_contents("log/".$runId.".txt", "第六行第一项\r\n",FILE_APPEND);
						} else if($data["DATA_80"] == "on") {
							$pijia = $data["DATA_142"];
							$gongyingshang = $data["DATA_143"];
							$dianhua = $data["DATA_141"];
							//file_put_contents("log/".$runId.".txt", "第六行第二项\r\n",FILE_APPEND);
						}
					}
			  //   	echo $pijia."<br>";
					// echo $gongyingshang."<br>";
					// echo $dianhua."<br>";
					//file_put_contents("log/".$runId.".txt", "-----------------------------------获取历史批价记录结束-----------------------------------\r\n\r\n",FILE_APPEND);
					$date = $data["DATA_273"];
					$date = substr($date, 0,10);
					return array("b_pricing" => $pijia,"b_vendor" => $gongyingshang,"b_vendor_tel" => $dianhua,"b_pricing_date" => $date);
				}
			}
		}
	}
}

// 获取存货列表
if($type == "getlist") {
	if($filter) {
		$sql = "select cInvName from Inventory where cInvName like '%".$filter."%'";
		//查询
		$result = $db->findAll($sql);
		$data = array("");
		foreach ($result as $key => $value) {
			array_push($data,$value["cInvName"]);
		}
		// if(count($data) > 100 && $filter)
			// $data =  array("请输入更详细的搜索条件！");
		$dataj = json_encode($data);
	} else {
		$data =  array("");
		$dataj = json_encode($data);
	}
} else if($type == "getinventorydetail") {
	// 获取存货详情
	// 用tfs的方式获取"inventory"表的信息【存货表】
	//if($f=="cInvCode") file_put_contents("log/".$runId.".txt", "cInvName = ".$relparent."\r\n",FILE_APPEND);
	$data = array();
	if($relparent) {
		$sql = "SELECT ".$f." FROM inventory WHERE cInvName = '".$relparent."'";
		$result = $db->find($sql);		
		if(count($result)){
			//if($f=="cInvCode") file_put_contents("log/".$runId.".txt", "cInvCode = ".$result[$f]."\r\n",FILE_APPEND);
			array_push($data,$result[$f]);
		}
	}
	$dataj = json_encode($data);
} else if($type == "getinventoryUnitName") {
	// 获取单位名称
	$data = array();
	//file_put_contents("log/".$runId.".txt", "-----------------------------------获取计量单位sql-----------------------------------".$runId."\r\n",FILE_APPEND);
	if($relparent) {
		$sql = "select ".$f." from ComputationUnit a, Inventory b where a.cComUnitCode = b.cComUnitCode AND b.cInvName = '".$relparent."'";
		//file_put_contents("log/".$runId.".txt", $sql."\r\n",FILE_APPEND);
		$result = $db->find($sql);
		if(count($result)){
			array_push($data,$result[$f]);
		}
		//file_put_contents("log/".$runId.".txt", $result[$f]."\r\n",FILE_APPEND);
	}
	$dataj = json_encode($data);
}else if($type == "b_ufserial") {
	// 获取单位名称
	$data = array();
	//file_put_contents("log/".$runId.".txt", "-----------------------------------获取用友单号-----------------------------------".$runId."\r\n",FILE_APPEND);
	$sql = "select ".$f." from PO_POMain where cdefine1 = '".$runId."'";
	//file_put_contents("log/".$runId.".txt", $sql."\r\n",FILE_APPEND);
	$result = $db->find($sql);
	if(count($result)){
		array_push($data,$result[$f]);
	}
	else{
		array_push($data,"");
	}
	//file_put_contents("log/".$runId.".txt", $result[$f]."\r\n",FILE_APPEND);
	$dataj = json_encode($data);
}else if($type == "b_pricing") {
	// 获取上次批价
	$data = array($history["b_pricing"]);
	$dataj = json_encode($data);
} else if($type == "b_vendor") {
	// 获取上次供应商
	$data = array($history["b_vendor"]);
	$dataj = json_encode($data);
} else if($type == "b_vendor_tel") {
	// 获取上次供应商电话
	$data = array($history["b_vendor_tel"]);
	$dataj = json_encode($data);
} else if($type == "b_pricing_date") {
	// 获取上次供应商
	$data = array($history["b_pricing_date"]);
	$dataj = json_encode($data);
}
echo $dataj;
?>
