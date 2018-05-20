<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.


/**
 * Prints a particular instance of tutorship for teachers view.
 *
 * The tutorship instance view that shows the teacher's tutoring
 * timetable configuration with time slots for student to reserve.
 *
 * @package   mod_tutorship
 * @copyright 2018 Daniel Baker
 * 
 */

defined('MOODLE_INTERNAL') || die(); // Direct access to this file is forbidden

//require_once($CFG->dirroot.'/lib/excellib.class.php');
//require_once($CFG->dirroot.'/lib/odslib.class.php');
//require_once($CFG->dirroot.'/lib/csvlib.class.php');
//require_once($CFG->dirroot.'/lib/pdflib.php');
 require_once($CFG->libdir . '/csvlib.class.php');

global $output;

// Security priviledges and layout
require_login($course, true, $cm);
require_capability('mod/tutorship:update', $PAGE->context);
$PAGE->set_pagelayout('admin');

//print_r($action);die();
    // Prints the heading and edit button
    $urlparams['id'] = $cm->id;
    $urlparams['t'] = $course->id;
    $urlparams['selectedperiod'] = $selectedperiod;
    $urlparams['action'] = 2;
    $urlparams['subpage'] = "contract";


	$c_teacherID = $USER->id;
	//$sql  = "SELECT * FROM tutorship_contract where teacherid=$c_teacherID AND course=$course->id";

	$contractInfo = $DB->get_record('tutorship_contract', array('teacherid' =>$c_teacherID , 'course'=>$course->id));

	$submitButtonStatus = true;
	$acceptedDate = "";
	if(isset($contractInfo) && isset($contractInfo->teacherid))
	{
		$submitButtonStatus = false;
		$acceptedDate = $contractInfo->createdate; 
	}

	if($_POST && $submitButtonStatus== true)
	{
		$accptedStatus = $_POST['chkAccepted'];
		if($accptedStatus == 'accpeted')
		{
			// Insert Data
			$insertID = tutorship_insert_contract($course->id , $c_teacherID);
			if($insertID != "")
			{
				$newContractInfo = $DB->get_record('tutorship_contract', array('id' =>$insertID));
				if(isset($newContractInfo) && isset($newContractInfo->teacherid))
				{
					$submitButtonStatus = false;
					$acceptedDate = $newContractInfo->createdate; 
				}
			}
		}
	}
	if($acceptedDate !="")
	{
		$acceptedDate = date('d/ m/ Y', $acceptedDate);
	
	}

	//$sql  = "SELECT * FROM tutorship_timetables GROUP BY teacherid";
	//$allUsers         = $DB->get_records_sql($sql, array());


    echo '<p>';
    //echo '<center>';

		echo "<div style='width: 100%;height: 450px;overflow-y:auto'>
			<center><h4>Terms and Conditions</h4></center>
			<p>General Site Usage</p>
			<p>Last Revised: December 16, 2013<p>
			<p></p>
			<p>Welcome to www.lorem-ipsum.info. This site is provided as a service to our visitors and may be used for informational purposes only. Because the Terms and Conditions contain legal obligations, please read them carefully.<p>
			<p></p>
			<p>1. YOUR AGREEMENT</p>
			<p>By using this Site, you agree to be bound by, and to comply with, these Terms and Conditions. If you do not agree to these Terms and Conditions, please do not use this site.</p>
			<p></p>
			<p>PLEASE NOTE: We reserve the right, at our sole discretion, to change, modify or otherwise alter these Terms and Conditions at any time. Unless otherwise indicated, amendments will become effective immediately. Please review these Terms and Conditions periodically. Your continued use of the Site following the posting of changes and/or modifications will constitute your acceptance of the revised Terms and Conditions and the reasonableness of these standards for notice of changes. For your information, this page was last updated as of the date at the top of these terms and conditions.</p>
			<p></p>
			<p>2. PRIVACY</p>
			<p>Please review our Privacy Policy, which also governs your visit to this Site, to understand our practices.</p>
			<p></p>
			<p>3. LINKED SITES</p>
			<p>This Site may contain links to other independent third-party Web sites ('Linked Sites'). These Linked Sites are provided solely as a convenience to our visitors. Such Linked Sites are not under our control, and we are not responsible for and does not endorse the content of such Linked Sites, including any information or materials contained on such Linked Sites. You will need to make your own independent judgment regarding your interaction with these Linked Sites.</p>
			<p></p>
			<p>4. FORWARD LOOKING STATEMENTS</p>
			<p>All materials reproduced on this site speak as of the original date of publication or filing. The fact that a document is available on this site does not mean that the information contained in such document has not been modified or superseded by events or by a subsequent document or filing. We have no duty or policy to update any information or statements contained on this site and, therefore, such information or statements should not be relied upon as being current as of the date you access this site.</p>
			<p></p>
			<p>5. DISCLAIMER OF WARRANTIES AND LIMITATION OF LIABILITY</p>
			<p>A. THIS SITE MAY CONTAIN INACCURACIES AND TYPOGRAPHICAL ERRORS. WE DOES NOT WARRANT THE ACCURACY OR COMPLETENESS OF THE MATERIALS OR THE RELIABILITY OF ANY ADVICE, OPINION, STATEMENT OR OTHER INFORMATION DISPLAYED OR DISTRIBUTED THROUGH THE SITE. YOU EXPRESSLY UNDERSTAND AND AGREE THAT: (i) YOUR USE OF THE SITE, INCLUDING ANY RELIANCE ON ANY SUCH OPINION, ADVICE, STATEMENT, MEMORANDUM, OR INFORMATION CONTAINED HEREIN, SHALL BE AT YOUR SOLE RISK; (ii) THE SITE IS PROVIDED ON AN 'AS IS' AND 'AS AVAILABLE' BASIS; (iii) EXCEPT AS EXPRESSLY PROVIDED HEREIN WE DISCLAIM ALL WARRANTIES OF ANY KIND, WHETHER EXPRESS OR IMPLIED, INCLUDING, BUT NOT LIMITED TO IMPLIED WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, WORKMANLIKE EFFORT, TITLE AND NON-INFRINGEMENT; (iv) WE MAKE NO WARRANTY WITH RESPECT TO THE RESULTS THAT MAY BE OBTAINED FROM THIS SITE, THE PRODUCTS OR SERVICES ADVERTISED OR OFFERED OR MERCHANTS INVOLVED; (v) ANY MATERIAL DOWNLOADED OR OTHERWISE OBTAINED THROUGH THE USE OF THE SITE IS DONE AT YOUR OWN DISCRETION AND RISK; and (vi) YOU WILL BE SOLELY RESPONSIBLE FOR ANY DAMAGE TO YOUR COMPUTER SYSTEM OR FOR ANY LOSS OF DATA THAT RESULTS FROM THE DOWNLOAD OF ANY SUCH MATERIAL.<p>
			<p></p>
			<p>B. YOU UNDERSTAND AND AGREE THAT UNDER NO CIRCUMSTANCES, INCLUDING, BUT NOT LIMITED TO, NEGLIGENCE, SHALL WE BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, PUNITIVE OR CONSEQUENTIAL DAMAGES THAT RESULT FROM THE USE OF, OR THE INABILITY TO USE, ANY OF OUR SITES OR MATERIALS OR FUNCTIONS ON ANY SUCH SITE, EVEN IF WE HAVE BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES. THE FOREGOING LIMITATIONS SHALL APPLY NOTWITHSTANDING ANY FAILURE OF ESSENTIAL PURPOSE OF ANY LIMITED REMEDY.</p>
			<p></p>
			<p>6. EXCLUSIONS AND LIMITATIONS</p>
			<p>SOME JURISDICTIONS DO NOT ALLOW THE EXCLUSION OF CERTAIN WARRANTIES OR THE LIMITATION OR EXCLUSION OF LIABILITY FOR INCIDENTAL OR CONSEQUENTIAL DAMAGES. ACCORDINGLY, OUR LIABILITY IN SUCH JURISDICTION SHALL BE LIMITED TO THE MAXIMUM EXTENT PERMITTED BY LAW.</p>
			<p></p>
			<p>7. OUR PROPRIETARY RIGHTS</p>
			<p>This Site and all its Contents are intended solely for personal, non-commercial use. Except as expressly provided, nothing within the Site shall be construed as conferring any license under our or any third party's intellectual property rights, whether by estoppel, implication, waiver, or otherwise. Without limiting the generality of the foregoing, you acknowledge and agree that all content available through and used to operate the Site and its services is protected by copyright, trademark, patent, or other proprietary rights. You agree not to: (a) modify, alter, or deface any of the trademarks, service marks, trade dress (collectively 'Trademarks') or other intellectual property made available by us in connection with the Site; (b) hold yourself out as in any way sponsored by, affiliated with, or endorsed by us, or any of our affiliates or service providers; (c) use any of the Trademarks or other content accessible through the Site for any purpose other than the purpose for which we have made it available to you; (d) defame or disparage us, our Trademarks, or any aspect of the Site; and (e) adapt, translate, modify, decompile, disassemble, or reverse engineer the Site or any software or programs used in connection with it or its products and services.</p>
			<p></p>
			<p>The framing, mirroring, scraping or data mining of the Site or any of its content in any form and by any method is expressly prohibited.</p>
			<p></p>
			<p>8. INDEMNITY</p>
			<p>By using the Site web sites you agree to indemnify us and affiliated entities (collectively 'Indemnities') and hold them harmless from any and all claims and expenses, including (without limitation) attorney's fees, arising from your use of the Site web sites, your use of the Products and Services, or your submission of ideas and/or related materials to us or from any person's use of any ID, membership or password you maintain with any portion of the Site, regardless of whether such use is authorized by you.</p>
			<p></p>
			<p>9. COPYRIGHT AND TRADEMARK NOTICE</p>
			<p>Except our generated dummy copy, which is free to use for private and commercial use, all other text is copyrighted. generator.lorem-ipsum.info © 2013, all rights reserved</p>
			<p></p>
			<p>10. INTELLECTUAL PROPERTY INFRINGEMENT CLAIMS</p>
			<p>It is our policy to respond expeditiously to claims of intellectual property infringement. We will promptly process and investigate notices of alleged infringement and will take appropriate actions under the Digital Millennium Copyright Act ('DMCA') and other applicable intellectual property laws. Notices of claimed infringement should be directed to:</p>
			<p></p>
			<p>generator.lorem-ipsum.info</p>
			<p></p>
			<p>126 Electricov St.</p>
			<p></p>
			<p>Kiev, Kiev 04176</p>
			<p></p>
			<p>Ukraine</p>
			<p></p>
			<p>contact@lorem-ipsum.info</p>
			<p></p>
			<p>11. PLACE OF PERFORMANCE</p>
			<p>This Site is controlled, operated and administered by us from our office in Kiev, Ukraine. We make no representation that materials at this site are appropriate or available for use at other locations outside of the Ukraine and access to them from territories where their contents are illegal is prohibited. If you access this Site from a location outside of the Ukraine, you are responsible for compliance with all local laws.</p>
			<p></p>
			<p>12. GENERAL</p>
			<p>A. If any provision of these Terms and Conditions is held to be invalid or unenforceable, the provision shall be removed (or interpreted, if possible, in a manner as to be enforceable), and the remaining provisions shall be enforced. Headings are for reference purposes only and in no way define, limit, construe or describe the scope or extent of such section. Our failure to act with respect to a breach by you or others does not waive our right to act with respect to subsequent or similar breaches. These Terms and Conditions set forth the entire understanding and agreement between us with respect to the subject matter contained herein and supersede any other agreement, proposals and communications, written or oral, between our representatives and you with respect to the subject matter hereof, including any terms and conditions on any of customer's documents or purchase orders.</p>
			<p></p>
			<p>B. No Joint Venture, No Derogation of Rights. You agree that no joint venture, partnership, employment, or agency relationship exists between you and us as a result of these Terms and Conditions or your use of the Site. Our performance of these Terms and Conditions is subject to existing laws and legal process, and nothing contained herein is in derogation of our right to comply with governmental, court and law enforcement requests or requirements relating to your use of the Site or information provided to or gathered by us with respect to such use.</p></div>";
  
    //echo '</center>';
    echo '</p>';

    echo '<p>';
	echo html_writer::start_tag('form', array('id' => 'searchform', 'method' => 'post', 'action' => ''));
	echo html_writer::start_tag('fieldset');
	
	echo '<input type="hidden" value="accpeted" name="chkAccepted" />';
	if($submitButtonStatus == true)
	{
		echo '<input type="submit" value="Accept Contract" class="btn btn-primary" />';
	}
	else
	{
		echo '<input type="submit" value="Accept Contract" class="btn btn-primary" disabled />';
		echo '<span style="margin-left: 20px">Date Accepted: '.$acceptedDate.'</span>';
	}
	echo html_writer::end_tag('fieldset');

	echo html_writer::end_tag('form');


    echo '</p>';


	echo '<style>.borderClass{border: 1px solid #666 !important} .borderClass > tbody  > tr > td {border: 1px solid #666 !important}  .borderClass > thead  > tr > th {border: 1px solid #666 !important}</style>';


