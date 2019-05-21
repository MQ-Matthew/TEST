<?php 
require_once('tcpdf_include.php');   
//实例化   
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);   
   
// 设置文档信息   
/*$pdf->SetCreator('Helloweba');   
$pdf->SetAuthor('yueguangguang');   
$pdf->SetTitle('Welcome to helloweba.com!');   
$pdf->SetSubject('TCPDF Tutorial');   
$pdf->SetKeywords('TCPDF, PDF, PHP');*/   
   
// 设置页眉和页脚信息   
$pdf->SetHeaderData('logo.png', 30, '', '',    
      array(0,64,255), array(0,64,128));   
$pdf->setFooterData(array(0,64,0), array(0,64,128));   
   
// 设置页眉和页脚字体   
$pdf->setHeaderFont(Array('stsongstdlight', '', '10'));   
$pdf->setFooterFont(Array('helvetica', '', '8'));   
   
// 设置默认等宽字体   
$pdf->SetDefaultMonospacedFont('courier');   
   
// 设置间距   
$pdf->SetMargins(15, 27, 15);   
$pdf->SetHeaderMargin(5);   
$pdf->SetFooterMargin(10);   
   
// 设置分页   
$pdf->SetAutoPageBreak(TRUE, 25);   
   
// set image scale factor   
//$pdf->setImageScale(1.25);   
   
// set default font subsetting mode   
$pdf->setFontSubsetting(true);   
   
//设置字体   
$pdf->SetFont('stsongstdlight', '', 14);   
   
$pdf->AddPage();   
   
$str1 = '';   
   
//$pdf->Write(0,$str1,'', 0, 'L', true, 0, false, false, 0);  

$html ='<table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td height="900" valign="top">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td height="60" align="center" valign="top" style="font-size:22px;"><strong>消耗材料采购计划申请</strong></td>
                    </tr>
                </table>

                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td><strong>申请科室：</strong></td>
                    </tr>
                </table>

                <table width="100%" border="0" cellspacing="0" cellpadding="0" style="border-top:1px solid #000;border-right:1px solid #000">
                    <tr>
                        <td width="16%" height="28" align="center" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:14px;"><strong>名称</strong></td>
                        <td width="11%" align="center" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:14px;"><strong>规格</strong></td>
                        <td width="11%" align="center" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:14px;"><strong>单位</strong></td>
                        <td width="10%" align="center" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:14px;"><strong>数量</strong></td>
                        <td width="10%" align="center" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:14px;"><strong>单价</strong></td>
                        <td width="12%" align="center" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:14px;"><strong>总价</strong></td>
                        <td width="17%" align="center" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:14px;"><strong>厂家品牌</strong></td>
                        <td width="13%" align="center" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:14px;"><strong>备注</strong></td>
                    </tr>
                    <tr>
                                <td height="28" style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:13px;">12121</td>
                                <td style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:13px;">12</td>
                                <td style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:13px;">12</td>
                                <td style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:13px;">12</td>
                                <td style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:13px;">12</td>
                                <td style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:13px;">144</td>
                                <td style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:13px;"></td>
                                <td style="border-left:1px solid #000;border-bottom:1px solid #000;font-size:13px;"></td>
                            </tr>                                    </table>

                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td height="40" colspan="3" style="border-left:1px solid #000;border-right:1px solid #000"><strong>&nbsp;经费来源</strong>：</td>
                    </tr>
                    <tr>
                        <td width="49%" height="15" style="border-left:1px solid #000;border-bottom:1px solid #000;"></td>
                        <td width="27%" style="border-bottom:1px solid #000;font-size:14px;"><strong>填表人：管理员</strong></td>
                        <td width="24%" style="border-bottom:1px solid #000;border-right:1px solid #000;font-size:14px;"><strong>日期：2014-09-16</strong></td>
                    </tr>
                </table>

                                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td width="25%" height="28" valign="top" style="border-left:1px solid #000;"><strong>申报科室主任：</strong></td>
                            <td width="25%" valign="top" style="border-left:1px solid #000;"><strong>财务科资金：</strong></td>
                            <td width="25%" valign="top" style="border-left:1px solid #000;border-right:1px solid #000;"><strong>总务科：</strong></td>
                            <td width="25%" valign="top" style="border-right:1px solid #000;"><strong>信息化管理部门：</strong></td>
                        </tr>
                        <tr>
                            <td height="50" valign="top" style="border-left:1px solid #000;border-bottom:1px solid #000">管理员:同意<br><br><span style="font-size:14px;">2014-09-16 11:16:03</span></td>
                            <td valign="top" style="border-left:1px solid #000;border-bottom:1px solid #000">&nbsp;</td>
                            <td valign="top" style="border-left:1px solid #000;border-bottom:1px solid #000;border-right:1px solid #000;">管理员:同意<br><br><span style="font-size:14px;">2014-09-16 11:16:23</span></td>
                          <td valign="top" style="border-bottom:1px solid #000;border-right:1px solid #000;">&nbsp;</td>
                        </tr>
                    </table>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                  <tr>
                            <td width="53%" height="28" valign="top" style="border-left:1px solid #000;"><strong>中心主管（申报科室）副主任审批：</strong></td>
                            <td width="47%" valign="top" style="border-left:1px solid #000;border-right:1px solid #000;"><strong>中心主管（采购）副主任审批：</strong></td>
                        </tr>
                        <tr>
                            <td height="50" style="border-left:1px solid #000;border-bottom:1px solid #000">管理员:同意<br><br><span style="font-size:14px;">2014-09-16 11:16:18</span></td>
                            <td style="border-right:1px solid #000;border-left:1px solid #000;border-bottom:1px solid #000">管理员:同意<br><br><span style="font-size:14px;">2014-09-16 11:16:30</span></td>
                        </tr>
                    </table>
                    <table width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td height="28" valign="top" style="border-left:1px solid #000;border-right:1px solid #000;"><strong>站领导审批：</strong></td>
                        </tr>
                        <tr>
                            <td height="50" style="border-right:1px solid #000;border-left:1px solid #000;border-bottom:1px solid #000">&nbsp;</td>
                        </tr>
                    </table>
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    <tr>
                        <td width="8%" align="right" valign="bottom"><strong style="font-size:14px;">注：</strong></td>
                        <td width="92%" height="30" valign="bottom"><strong style="font-size:14px;">1、本表一式三份，财务科一份，总务科一份，使用科室留存一份。</strong></td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td height="30"> <strong style="font-size:14px;">2、2000元以下中心主管副主任签字，2000元以上中心主任签字。</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>';
$pdf->writeHTML($html, true, false, true, false, ''); 
   
//输出PDF   
$pdf->Output('22222222222222.pdf', 'F');  
?>