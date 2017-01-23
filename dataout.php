<?
require "sqlsrv_driver.php";
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
//http://125.88.4.168:8082/building/data_out/dataout.php
$db = SQLSrv::getdatabase($_db);

include_once("inc/oa_config.php");
include_once("inc/conn.php");

// 调试开关
// $flag = "test";
$flag = "produce";


// 测试
if($flag == "test") {
    $data = file_get_contents("test_data.txt");
    $data = json_decode($data,true);
}
// 生产
if($flag == "produce") {
    $data = $_REQUEST;
}
if(!$data["RUN_ID"]) {
    exit();
}
file_put_contents("log/".$data["RUN_ID"].".txt", "-----------------------------------数据-----------------------------------"."\r\n"."\r\n",FILE_APPEND);
file_put_contents("log/".$data["RUN_ID"].".txt", iconv("UTF-8","GB2312//IGNORE",json_encode($data)) ."\r\n",FILE_APPEND);

// 1、获取 1500.... 的ID [主表流水号]
$sql1 = "select cNumber as Maxnumber From VoucherHistory with (UPDLOCK) Where CardNumber='88' and cContent is NULL";
$result1 = $db->find($sql1);
if(count($result1)) {
    $Maxnumber = $result1["Maxnumber"];
    $Maxnumber = "15".str_pad($Maxnumber+1,8,"0",STR_PAD_LEFT);
    // 更新主表流水号
    $sqlUpdate = "update VoucherHistory set cNumber=cNumber+1 Where CardNumber='88' and cContent is NULL";
    if($flag == "produce") {
        $db->query($sqlUpdate);
    }
}
//*********计算明细条数
$Detailsnum = 0;
for ($i=1; $i < 7; $i++) {
    $pinming = $data["品名".$i];	
    $shuliang = $data[$i."批准数量"];	
	if($shuliang=="") $shuliang = 0;
	//file_put_contents("log/".$data["RUN_ID"].".txt", "shuliang1 = ".$shuliang."\r\n"."\r\n",FILE_APPEND);
    if($pinming)
	{		
		//file_put_contents("log/".$data["RUN_ID"].".txt", "pinming = ".$pinming."\r\n"."\r\n",FILE_APPEND);
		//file_put_contents("log/".$data["RUN_ID"].".txt", "shuliang2 = ".$shuliang."\r\n"."\r\n",FILE_APPEND);
		if($shuliang>0)
		{
			//file_put_contents("log/".$data["RUN_ID"].".txt", "shuliang3 = ".$shuliang."\r\n"."\r\n",FILE_APPEND);
			$Detailsnum = $Detailsnum + 1;
			//file_put_contents("log/".$data["RUN_ID"].".txt", "Detailsnum1 = ".$Detailsnum."\r\n"."\r\n",FILE_APPEND);
		}
	}
}
file_put_contents("log/".$data["RUN_ID"].".txt", "Detailsnum = ".$Detailsnum."\r\n"."\r\n",FILE_APPEND);
if($Detailsnum==0)
{
	file_put_contents("log/".$data["RUN_ID"].".txt", "没有正确的明细条目，直接退出不抛转\r\n"."\r\n",FILE_APPEND);
	return;
}
// 2、获取主表及明细表账单号
$sql2 = "select iFatherId,iChildId from UFSystem..UA_Identity with (updlock) where CAcc_id='666' and cVouchType='Pomain'";
$result2 = $db->find($sql2);
if(count($result2)) {
    $iFatherId = $result2["iFatherId"];
    $iFatherId = "15".str_pad($iFatherId+1,7,"0",STR_PAD_LEFT);
    $iChildId = $result2["iChildId"];
    $iChildId = "15".str_pad($iChildId+1,7,"0",STR_PAD_LEFT);
    // 这句里面的   with (UPDLOCK)  去掉了，会报错*******ChildID增量改为实际的明细条数
    $sqlUpdate1 = "update UFSystem..UA_Identity set iFatherId=".($result2["iFatherId"]+1).",iChildId=".($result2["iChildId"]+$Detailsnum)." where CAcc_id='666' and cVouchType='Pomain'";
    if($flag == "produce") {
        $db->query($sqlUpdate1);
    }
}
if($flag == "test") {
    echo "Maxnumber = ".$Maxnumber."<hr>";
    echo "iFatherId = ".$iFatherId."<hr>";
    echo "iChildId = ".$iChildId."<hr>";
}
$date = date("Y-m-d");
$icven = $data["采购人"];
$icdep = $data["申购部门"];
$icven = str_replace("（","(",$icven);
$icven = str_replace("）",")",$icven);
$icdep = str_replace("（","(",$icdep);
$icdep = str_replace("）",")",$icdep);
preg_match_all("/(?:\()(.*)(?:\))/i" , $icven , $icvendata);
$icvencode = $icvendata[1][0];
preg_match_all("/(?:\()(.*)(?:\))/i" , $icdep , $icdepdata);
$icdepcode = $icdepdata[1][0];

if($icvencode=="") $icvencode = "0000";
if($icdepcode=="") $icdepcode = "0000";
file_put_contents("log/".$data["RUN_ID"].".txt", $icven."---->",FILE_APPEND);
file_put_contents("log/".$data["RUN_ID"].".txt", $icvencode."\r\n",FILE_APPEND);
file_put_contents("log/".$data["RUN_ID"].".txt", $icdep."---->",FILE_APPEND);
file_put_contents("log/".$data["RUN_ID"].".txt", $icdepcode."\r\n",FILE_APPEND);
// 3、插入主表  第22项cdefine1添加RUNID，为将来回显用友订单号做索引。
$inssql1 = "
if not exists (select cpoid from PO_POMain where cpoid=N'".$Maxnumber."' or poid=N'".$iFatherId."')  Insert Into
 PO_POMain(cpoid,dpodate,cvencode,cdepcode,cpersoncode,cptcode,carrivalplace,csccode,cexch_name,nflat,itaxrate,cpaycode,icost,ibargain,cmemo,cstate,cperiod,cmaker,cverifier,ccloser,cdefine1,cdefine2,cdefine3,cdefine4,cdefine5,cdefine6,cdefine7,cdefine8,cdefine9,cdefine10,poid,ivtid,cchanger,cbustype,cdefine11,cdefine12,cdefine13,cdefine14,cdefine15,cdefine16,dharrivedate,caccountpid,caccountpdate,defaultcall,coutverifier,coutcontrol,coutstatus,dcoutveriddate) 
 Values ('".$Maxnumber."','".$date."','".$icvencode."','".$icdepcode."',NULL,'01',NULL,NULL,'人民币',1,NULL,NULL,0,0,'OA抛转用友系统单据',0,NULL,'OA系统',NULL,NULL,'".$data["RUN_ID"]."',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,".$iFatherId.",23,NULL,'普通采购',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'".$date."',NULL,NULL,NULL,NULL,NULL)";
if($flag == "test") {
    echo $inssql1."<br>";
}
if($flag == "produce") {
    $insResult = $db->query($inssql1);
}
file_put_contents("log/".$data["RUN_ID"].".txt", "-----------------------------------主表sql-----------------------------------"."\r\n",FILE_APPEND);
file_put_contents("log/".$data["RUN_ID"].".txt", $inssql1."\r\n",FILE_APPEND);
file_put_contents("log/".$data["RUN_ID"].".txt", $insResult."\r\n",FILE_APPEND);

file_put_contents("log/".$data["RUN_ID"].".txt", "-----------------------------------明细计算-----------------------------------"."\r\n",FILE_APPEND);
// 组织明细数据
for ($i=1; $i < 7; $i++) {
    $pinming = $data["品名".$i];
	$shuliang = $data[$i."批准数量"];		
	if($shuliang=="") $shuliang = 0;	
	file_put_contents("log/".$data["RUN_ID"].".txt", "shuliang = ".$shuliang."\r\n"."\r\n",FILE_APPEND);
    if($pinming&&($shuliang>0)) {
        $bianma = $data["编码".$i];
		$bianma = str_replace(',', '',$bianma);         
		$jiage = $data[$i."批准价格"];
		$jiage = str_replace(',', '',$jiage);			
		if($jiage=="") $jiage = 0;	
		$daipi_gongyingshang  = $data[$i."批准供应商"];
        		
		//税率******原文件中税额税率混淆
		$iPerTaxRate = $data["批准税率".$i];
		if($iPerTaxRate=="")
		{
			$iPerTaxRate = 0;
			//file_put_contents("log/".$data["RUN_ID"].".txt", "iPerTaxRate set 0\r\n",FILE_APPEND);
		}
		else
		{
			$iPerTaxRate = str_replace(',', '',$iPerTaxRate); 
			//file_put_contents("log/".$data["RUN_ID"].".txt", "iPerTaxRate = ".$iPerTaxRate."\r\n",FILE_APPEND);
		}
		
        // 原币无税单价=含税单价/(1+税率)
        $iUnitPrice = number_format($jiage/(1+$iPerTaxRate/100),2);
		$iUnitPrice = str_replace(',', '',$iUnitPrice); 
		//file_put_contents("log/".$data["RUN_ID"].".txt", "iUnitPrice = ".$iUnitPrice."\r\n",FILE_APPEND);
		
        // 原币无税金额=含税单价/(1+税率)*数量
        $iMoney = number_format($shuliang*$jiage/(1+$iPerTaxRate/100),2);
		$iMoney = str_replace(',', '',$iMoney); 
		//file_put_contents("log/".$data["RUN_ID"].".txt", "iMoney = ".$iMoney."\r\n",FILE_APPEND);
		
		// 原币税额=含税单价/(1+税率)*数量*税率
        $iTax = number_format($shuliang*$jiage/(1+$iPerTaxRate/100)*($iPerTaxRate/100),2);
		$iTax = str_replace(',', '',$iTax); 
		//file_put_contents("log/".$data["RUN_ID"].".txt", "iTax = ".$iTax."\r\n",FILE_APPEND);
		
        // 原币含税单价=含税单价/(1+税率)*数量*税率
        $iTaxprice = number_format($jiage,2);
		$iTaxprice = str_replace(',', '',$iTaxprice); 
		//file_put_contents("log/".$data["RUN_ID"].".txt", "iTaxprice = ".$iTaxprice."\r\n",FILE_APPEND);
		
        // 原币价税合计=原币无税金额+原币税额=含税单价*(1+税率)/(1+税率)*数量=含税单价*数量
        $iSum = number_format($shuliang*$jiage,2);
		$iSum = str_replace(',', '',$iSum);
		//file_put_contents("log/".$data["RUN_ID"].".txt", "iiSum = ".$iSum."\r\n",FILE_APPEND);
		
		file_put_contents("log/".$data["RUN_ID"].".txt", "-----------------------------------子表sql-----------------------------------"."\r\n",FILE_APPEND);
        // 4、插入明细表******原文件中税额税率混淆
        $itemsql = "
if not exists (select id from PO_PODetails where id=N'".$iChildId."') Insert Into PO_PODetails(
id,cpoid,cinvcode,iquantity,inum,iquotedprice,iunitprice,imoney,itax,isum,idiscount,inatunitprice,inatmoney,inattax,inatsum,inatdiscount,darrivedate,cfree1,cfree2,inatinvmoney,ipertaxrate,cdefine22,cdefine23,cdefine24,cdefine25,cdefine26,cdefine27,iflag,citemcode,citem_class,ppcids,citemname,cfree3,cfree4,cfree5,cfree6,cfree7,cfree8,cfree9,cfree10,bgsp,
poid,cunitid,itaxprice,iappids,cdefine28,cdefine29,cdefine30,cdefine31,cdefine32,cdefine33,cdefine34,cdefine35,cdefine36,cdefine37,isosid,btaxcost,csource,csosids,irelationid,yccode,ycsid,csocode,sosid)
Values
(".$iChildId.",NULL,'".$bianma."','".$shuliang."',NULL,NULL,'".$iUnitPrice."','".$iMoney."','".$iTax."','".$iSum."',NULL,'".$iUnitPrice."','".$iMoney."','".$iTax."','".$iSum."',NULL,NULL,NULL,NULL,NULL,'".$iPerTaxRate."',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0,
'".$iFatherId."',NULL,'".$iTaxprice."',NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL)
        ";
		
        file_put_contents("log/".$data["RUN_ID"].".txt", $itemsql."\r\n",FILE_APPEND);
        if($flag == "test") {
            echo $iMoney."<br>";
            echo $shuliang."<br>";
            echo $iUnitPrice."<br>";
            echo $itemsql."<hr>";
        }
        if($flag == "produce") {
            $itemInsResult = $db->query($itemsql);
        }
		$iChildId = $iChildId + 1;
        file_put_contents("log/".$data["RUN_ID"].".txt", $itemInsResult."\r\n",FILE_APPEND);
    }
}

?>
