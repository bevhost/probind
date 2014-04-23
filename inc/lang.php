<?php

function trans($str,$param="") {
	global $language, $tr;
	if (!array_key_exists($language,$tr)) echo "<!-- no translations for '$language' -->";
	if (isset($tr[$language][$str])) $str = $tr[$language][$str];
	if ($param) {
		if (isset($tr[$language][$param])) $param = $tr[$language][$param];
	}
	if (strpos('%s',$str)) {
		$str = sprintf($str,$param);
	}
	return $str;
}

$languages = array(
	'en' => 'English',
	'zh' => '中国的',
	'fr' => 'French',
);
	
$tr = array( 

   'en' => array(
	"InvalidDomainName"=>"The domain name %s is invalid",
	"InvalidIpAddress"=>"Invalid IP Address: %s",
	"InvalidType"=>"Invalid DNS Resource Record Type %s, should by A, AAAA, MX, TXT, CNAME etc.",
	"PortRequired"=>"You must supply a Port number for SRV records",
	"WeightRequired"=>"You must supply a Weight number for SRV records",
	"PriorityRequired"=>"You must supply a Priority number for MX or SRV records",
	"BadCNAME"=>"A CNAME may not exist for the entire domain",
	"DomainOutsideZone"=>"Domain %s is outside the zone",
	"RequiredField"=>"%s must not be left blank",
	"InvalidUnsignedInt16"=>"%s must be a number 0 to 65535",
	"-"=>"-",   /* end client side, below only server side php, above both client javascript and server php */
	"mtime"=>"Modified",
	"ctime"=>"Created",
	"genptr"=>"Generate PTR",
	),


   'zh' => array(
	"InvalidDomainName"=>"域名无效的",
	"InvalidIPAddress"=>"无效的IP地址",
        "Type"=>"类型",
        "Pref"=>"首选项",
        "Data"=>"域名",
	"Zone"=>"区",
        "Domain"=>"域",
	"Yes"=>"是的",
	"No"=>"没有",
	"-"=>"-",   /* end client side, below only server side php, above both client javascript and server php */
	"N/A"=>"不适用",
	"Browse zones"=>"浏览区",
        "Browse records"=>"浏览记录",
        "Add a zone"=>"添加区域",
        "Delete a zone"=>"删除区域",
        "Misc. tools"=>"杂项”工具",
        "Push updates"=>"推送更新",
        "Logout"=>"登出",
        "Domain name"=>"域名",
        "Owner"=>"所有者",
	"Host"=>"主机",
        "Search"=>"搜索",
	"For"=>"于",
        "Master"=>"大师",
        "Master server"=>"主服务器",
	"Master zones"=>"大师区",
	"Slave zones"=>"奴隶区",
	"All zones"=>"凡区",
	"Annotations"=>"主机",
        "Slave"=>"奴隶",
        "First"=>"第一",
        "Next"=>"下一步",
        "Prev"=>"上一页",
        "Last"=>"最后",
        "Help"=>"帮助",
        "Pushing DNS updates to the servers"=>"推DNS更新服务器",
	"Server"=>"服务器",
	"No such domain"=>"没有这样的域名",
	"Enter one or more names of domains to add to the database, each on a separate line"=>"一个单独的行上输入一个或多个名称的域添加到数据库中，每个",
	),

   'fr' => array(
	"-"=>"-",   /* end client side, below only server side php, above both client javascript and server php */
	"Browse zones"=>"Naviguez zones",
	"Browse records"=>"Registres parcourir",
	"Add a zone"=>"Ajouter une zone",
	"Delete a zone"=>"Supprimer une zone",
	"Misc. tools"=>"Misc. Outils",
	"Push updates"=>"Poussez les mises à jour",
	"Logout"=>"Déconnexion",
	),

);

function javascript_translations($language) {
?>
<script type='text/javascript'>
var ipv4 = String('<?php echo $GLOBALS["IPV4_RE_JS"]; ?>');
var ipv6 = String('<?php echo $GLOBALS["IPV6_RE_JS"]; ?>');
var domreg = '<?php echo $GLOBALS["DOMAIN_RE_JS"]; ?>';
var message = {
<?php
  $final = array();
  foreach($GLOBALS["tr"] as $lang => $trans) {	
    $stop = false;
    foreach($trans as $key => $val) {
	if ($key=="-") $stop = true;
	if (!$stop) {
		if (!isset($final[$key])) $final[$key] = $val;			/* Take the first thing you see */
		else if ($lang==$language) $final[$key] = $val;	/* Overwrite it with the language you desire, if found */
	}
    }
  }
  foreach($final as $key => $val) {
	echo "\t".str_replace(" ","",$key).":\"$val\",\n";
  }
  echo "\n};\n</script>\n";
};

?>
