<?php
class Sla_Report extends CI_Controller{
	var $title = 'รายงานผลการดำเนินงานตาม Service Level Agreement (SLA) ';
	var $sla_rule = array(
		'2560'=>array(
			1=>array(
				'name'=>'การแจ้งผลการพิจารณาข้อเสนอโครงการกลับไปยังนักวิจัย',
				'target'=>'ภายใน 60 วัน นับจากวันที่ได้รับข้อเสนอโครงการ',
				'url_transaction'=>'sla_report/api/sla_report_60_1',
			),
			2=>array(
				'name'=>'การลงทะเบียนรับเอกสารโครงการวิจัย พร้อมจัดทำและส่งออกจดหมายตอบรับการได้รับเอกสารโครงการการวิจัย',
				'target'=>'ภายใน 2 วันทำการ นับจากวันที่ได้รับเอกสาร',
				'url_transaction'=>'sla_report/api/sla_report_60_2',
			),
			3=>array(
				'name'=>'การทำเรื่องเบิกจ่ายค่าตอบแทนให้ผู้ประเมินหลังจากได้รับผลประเมินแล้ว',
				'target'=>'ภายใน 2 วันทำการ นับจากวันที่ได้รับผลประเมิน',
				'url_transaction'=>'sla_report/api/sla_report_60_3',
			),
			4=>array(
				'name'=>'การแจ้งผลการประเมินรายงานและเบิกจ่ายเงินงวดโครงการวิจัย (กรณีไม่มีการปรับแก้)',
				'target'=>'ภายใน 30 วันทำการ นับจากวันที่ได้รับรายงาน',
				'url_transaction'=>'sla_report/api/sla_report_60_4',
			),
			5=>array(
				'name'=>'การส่งออกจดหมายขอบคุณวิทยากรในกิจกรรมถ่ายทอดเทคโนโลยี',
				'target'=>'ภายใน 2 วันทำการ นับจากวันที่เสร็จสิ้นการจัดกิจกรรม',
				'url_transaction'=>'sla_report/api/sla_report_60_5',
			),
			6=>array(
				'name'=>'การให้คำปรึกษาเบื้องต้นแก่ผู้ประกอบการที่ติดต่อสอบถามเข้ามา',
				'target'=>'ภายใน 3 วันทำการ นับจากวันที่ได้รับข้อมูลจากระบบ',
				'url_transaction'=>'',
			),

		)
	);


	function __construct()
	{
		parent::__construct();
        
		//Authentication
		$this->load->model('sso_model');

        $uri_allow = array('api');

        if (!in_array($this->uri->segment(2), $uri_allow))
        {
            $this->sso_model->login();
        }

        //Load Model Require
        $this->load->model('global_model');
        $this->load->model('sla_report_model');
        $this->load->model('curl_model');
        $this->load->model('rd_project_model');
        $this->load->model('rd_proposal_model');
        $this->load->model('rd_proposal_doc_model');
        $this->load->model('letterdb_model');
        $this->load->model('rtap_model');
        $this->load->model('namecard_model');
        //

	}


	function index($sel_year='')
	{
		if ($sel_year=='') 
		{
			$prefix = 0;
			if (date("m")>=10)
			{
				$prefix = 1;
			}
			$sel_year = date("Y")+543+$prefix;
		}
		$data['sel_year'] = $sel_year;
		if (isset($this->sla_rule[$sel_year]))
		{
			$data['sel_year'] = $sel_year;
			$data['sla_rule'] = $this->sla_rule[$sel_year];
			$Data = $this->load->view('sla_report/sla_report_table',$data,true);
		}
		else
		{
			$Data = '<center>No SLA Rule Data</center>';
		}
		$data['content'] = $Data;
		$data['title'] = $this->title. ' ปีงบประมาณ ' .$sel_year;
		$this->load->view('nnr_index',$data);
	}

	function api($method)
	{
		$data = array();
		switch ($method) {
			case 'sla_report_60_5':
				$rtap_project_list = array();
				$sel_month = $this->input->get_post('month');
				$sel_year = $this->input->get_post('year');
				$sla_target = 2;

				$this->db->from('nn_rtap_project');
				$this->db->where('rtap_project_id IS NOT NULL');
				$this->db->where('month(project_end)',$sel_month);
				$this->db->where('year(project_end)',$sel_year);
				$this->db->order_by('rtap_project_id','asc');
				$query = $this->db->get();
				#echo $this->db->last_query();
				if ($query->num_rows()>0)
				{
					$count = $query->num_rows();
					$success_count = 0;
					foreach ($query->result() as $rows) {
						$sla_target_date = new DateTime($this->global_model->find_workday($rows->project_end,$sla_target));

						$rtap_project_id =  $rows->rtap_project_id;
						$sla_response = new DateTime();

						$interval = $sla_response->diff($sla_target_date);
						$diff_date = $interval->format("%R%a");

						if ($sla_response->format("Y-m-d")==date("Y-m-d"))
						{
							if ($diff_date>=0)
							{
								$sla_status = "wait";
							}
							else
							{
								$sla_status = false;
							}
						}
						else
						{
							if ($diff_date>=0)
							{
								$sla_status = true;
								$success_count++;
							}
							else
							{
								$sla_status = false;
							}
						}

						array_push($rtap_project_list,array(
							'rtap_project_id'=>$rtap_project_id,
							'project_name'=>$this->rtap_model->get_value($rows->rtap_project_id,'project_name'),
							'project_end'=>$rows->project_end,
							'sla_target_date'=>$sla_target_date->format("Y-m-d"),
							'sla_response'=>$sla_response->format("Y-m-d"),
							'diff_date'=>$diff_date,
							'sla_status'=>$sla_status,
						));
					}

					array_push($data,array(
						'transaction'=>array(
							'count'=>$count,
							'success_count'=>$success_count,
							'data'=>$rtap_project_list
						)
					));
				}
				else
				{
					array_push($data,array(
						'transaction'=>array(
							'count'=>0,
							'success_count'=>0,
						)
					));
				}
			break;
			case 'sla_report_60_4':
				$sel_month = $this->input->get_post('month');
				$sel_year = $this->input->get_post('year');
				$sla_target = 30;
				$rd_project_list = array();
				$this->db->from('nn_rd_project_tracking');
				$this->db->where('(tracking_key = "in_receive_progress_report" OR tracking_key = "in_receive_final_report")');
				$this->db->where('month(tracking_date)',$sel_month);
				$this->db->where('year(tracking_date)',$sel_year);
				$this->db->order_by('tracking_date','asc');
				$query = $this->db->get();

				if ($query->num_rows()>0)
				{
					$count = $query->num_rows();
					$success_count = 0;
					foreach ($query->result() as $rows) {
						$sla_target_date = new DateTime($this->global_model->find_workday($rows->tracking_date,$sla_target));
						$tracking_date = new DateTime($rows->tracking_date);

						switch ($rows->tracking_key) {
							case 'in_receive_progress_report':
								$sla_response = $this->rd_project_model->get_value($rows->project_id,'ap_2_rdate');
							break;

							case 'in_receive_final_report':
								$sla_response = $this->rd_project_model->get_value($rows->project_id,'ap_3_rdate');
							break;

							default:
								$sla_response = date("Y-m-d");
							break;
						}
						
						if ($sla_response=="")
						{
							$sla_response = date("Y-m-d");
						}
						$sla_response = new DateTime($sla_response);

						$interval = $sla_response->diff($sla_target_date);
						$diff_date = $interval->format("%R%a");

						if ($diff_date>0)
						{
							$sla_status = 'true';
							$success_count++;
						}
						else
						{
							$sla_status = 'false';
						}

						array_push($rd_project_list,array(
							'project_id'=>$rows->project_id,
							'project_name'=>$this->rd_project_model->get_value($rows->project_id,'project_th_name'),
							'tracking_key'=>$rows->tracking_key,
							'tracking_date'=>$tracking_date->format("Y-m-d"),
							'sla_target_date'=>$sla_target_date->format("Y-m-d"),
							'sla_response'=>$sla_response->format("Y-m-d"),
							'diff_date'=>$diff_date,
							'sla_status'=>$sla_status,
						));
					}

					array_push($data,array(
						'transaction'=>array(
							'count'=>$count,
							'success_count'=>$success_count,
							'data'=>$rd_project_list
						)
					));
				}
				else
				{
					array_push($data,array(
						'transaction'=>array(
							'count'=>0,
							'success_count'=>0,
						)
					));
				}
			break;
			case 'sla_report_60_3':
				$sel_month = $this->input->get_post('month');
				$sel_year = $this->input->get_post('year');
				$sla_target = 2;
				$rd_project_list = array();
				$this->db->from('nn_rd_project_tracking');
				$this->db->where('(tracking_key = "in_receive_progress_report_from_specialist_1" OR tracking_key = "in_receive_progress_report_from_specialist_2" OR tracking_key = "in_receive_final_report_from_specialist_1" OR tracking_key = "in_receive_final_report_from_specialist_2")');
				$this->db->where('month(tracking_date)',$sel_month);
				$this->db->where('year(tracking_date)',$sel_year);
				$this->db->order_by('tracking_date','asc');
				$query = $this->db->get();

				if ($query->num_rows()>0)
				{
					$count = $query->num_rows();
					$success_count = 0;
					foreach ($query->result() as $rows) {
						$sla_target_date = new DateTime($this->global_model->find_workday($rows->tracking_date,$sla_target));
						$tracking_date = new DateTime($rows->tracking_date);

						$this->db->from('nn_rd_project_tracking');
						$this->db->where('project_id',$rows->project_id);
						switch ($rows->tracking_key) {
							case 'in_receive_progress_report_from_specialist_1':
								$this->db->where('tracking_key','out_acc_payment_to_progress_report_specialist_1');
							break;
							case 'in_receive_progress_report_from_specialist_2':
								$this->db->where('tracking_key','out_acc_payment_to_progress_report_specialist_2');
							break;
							case 'in_receive_final_report_from_specialist_1':
								$this->db->where('tracking_key','out_acc_payment_to_final_report_specialist_1');
							break;
							case 'in_receive_final_report_from_specialist_2':
								$this->db->where('tracking_key','out_acc_payment_to_final_report_specialist_2');
							break;
						}
						$query_sla = $this->db->get();
						if ($query_sla->num_rows()>0)
						{
							$rows_sla = $query_sla->rows();
							$sla_response = new DateTime($rows_sla->tracking_date);
						}
						else
						{
							$sla_response = new DateTime();
						}

						$interval = $sla_response->diff($sla_target_date);
						$diff_date = $interval->format("%R%a");

						if ($diff_date>=0)
						{
							$sla_status = 'true';
							$success_count++;
						}
						else
						{
							$sla_status = 'false';
						}


						$specialist_name = '';

						$this->db->from('nn_rd_project_specialist');
						$this->db->where('project_id',$rows->project_id);
						$query_specialist = $this->db->get();
						if ($query_specialist->num_rows()>0)
						{
							foreach ($query_specialist->result() as $rows_specialist) {
								$specialist_name .= $this->namecard_model->get_value($rows_specialist->card_id,'fullname'). ',';
							}
						}

						array_push($rd_project_list,array(
							'project_id'=>$rows->project_id,
							'project_name'=>$this->rd_project_model->get_value($rows->project_id,'project_th_name'),
							'specialist_name'=>$specialist_name,
							'project_management_name'=>$this->rd_project_model->get_value($rows->project_id,'username'),
							'tracking_key'=>$rows->tracking_key,
							'tracking_date'=>$tracking_date->format("Y-m-d"),
							'sla_target_date'=>$sla_target_date->format("Y-m-d"),
							'sla_response'=>$sla_response->format("Y-m-d"),
							'diff_date'=>$diff_date,
							'sla_status'=>$sla_status,
						));
					}

					array_push($data,array(
						'transaction'=>array(
							'count'=>$count,
							'success_count'=>$success_count,
							'data'=>$rd_project_list
						)
					));
				}
				else
				{
					array_push($data,array(
						'transaction'=>array(
							'count'=>0,
							'success_count'=>0,
						)
					));
				}

			break;

			case 'sla_report_60_2':
				$sel_month = $this->input->get_post('month');
				$sel_year = $this->input->get_post('year');
				$sla_target = 2;
				$proposal_id_list = array();
				$begin_date = new DateTime($sel_year. '-' .$sel_month. '-01');
				$query = $this->rd_proposal_model->query_proposal('month(proposal_date)=' .$begin_date->format("m"). ' AND year(proposal_date)=' .$begin_date->format("Y"));
				#echo $this->db->last_query();
				if ($query->num_rows()>0)
				{
					$count = $query->num_rows();
					$success_count = 0;
					foreach ($query->result() as $rows) {
						$proposal_response = $this->rd_proposal_doc_model->get_record_id('proposal_id = "' .$rows->proposal_id. '" AND doc_key = "receive_proposal"');
						$proposal_date = new DateTime($rows->proposal_date);
						
						if ($proposal_response!='')
						{
							$response_date = new DateTime($this->letterdb_model->get_value($proposal_response,'letterdate'));
						}
						else
						{
							$response_date = new DateTime();
						}
						$sla_target_date = new DateTime($this->global_model->find_workday($rows->proposal_date,$sla_target));

						$interval = $response_date->diff($sla_target_date);
						$diff_date = $interval->format('%R%a');
						if ($diff_date>=0)
						{
							$sla_status = 'true';
							$success_count++;
						}
						else
						{
							$sla_status = 'false';
						}
						array_push($proposal_id_list,
							array(
								'proposal_id'=>$rows->proposal_id,
								'proposal_name'=>$rows->proposal_name,
								'proposal_date'=>$proposal_date->format("Y-m-d"),
								'sla_target_date'=>$sla_target_date->format("Y-m-d"),
								'sla_response'=>$response_date->format("Y-m-d"),
								'diff_date'=>$diff_date,
								'sla_status'=>$sla_status
							)
						);
					}
					$data[0]['transaction']['count'] = $count;
					$data[0]['transaction']['success_count'] = $success_count;
					$data[0]['transaction']['data'] = $proposal_id_list;
				}
				else
				{
					array_push($data,
				 		array(
				 			'transaction'=>array(
				 				'count'=>0,
				 				'success_count'=>0
				 			),
				 		)
				 	);
				}
			break;

			case 'sla_report_60_1':
				$proposal_id_list = array();
				$sel_month = $this->input->get_post('month');
				$sel_year = $this->input->get_post('year');
				$sla_target = 60;

				$begin_date = new DateTime($sel_year. '-' .$sel_month. '-01');
				$begin_date->modify("-60 days");


				$query = $this->rd_proposal_model->query_proposal('month(proposal_date)=' .$begin_date->format("m"). ' AND year(proposal_date)=' .$begin_date->format("Y"));
				#echo $this->db->last_query();
				if ($query->num_rows()>0)
				{
					$transaction = $query->num_rows();
					$success_count = 0;
					foreach ($query->result() as $rows) {
						$proposal_response = $this->rd_proposal_doc_model->get_record_id('proposal_id = "' .$rows->proposal_id. '" AND doc_key = "proposal_result_response"');
						$proposal_date = new DateTime($rows->proposal_date);
						
						if ($proposal_response!='')
						{
							$response_date = new DateTime($this->letterdb_model->get_value($proposal_response,'letterdate'));
						}
						else
						{
							$response_date = new DateTime();
						}

						$success_date = $response_date->format("Y-m-d");

						$sla_target_date = new DateTime($rows->proposal_date);
						$sla_target_date->modify("+60 days");
						//SLA Status
						$interval = $response_date->diff($sla_target_date);
						$diff_date = $interval->format('%R%a');

						if ($response_date->format("Y-m-d")==date("Y-m-d"))
						{
							if ($diff_date>=0)
							{
								$sla_status = 'wait';
							}
							else
							{
								$sla_status = 'false';
							}
						}
						else
						{
							if ($diff_date>=0)
							{
								$sla_status = 'true';
								$success_count++;
							}
							else
							{
								$sla_status = 'false';
							}
						}

						
						array_push($proposal_id_list, 
							array(
								'proposal_id'=>$rows->proposal_id,
								'proposal_name'=>$rows->proposal_name,
								'proposal_date'=>$rows->proposal_date,
								'sla_target_date'=>$sla_target_date->format("Y-m-d"),
								'sla_response'=>$success_date,
								'diff_date'=>$diff_date,
								'sla_status'=>$sla_status,
							)
						);
					 } 

					 array_push($data,
					 		array(
					 			'transaction'=>array(
					 				'count'=>$transaction,
					 				'success_count'=>$success_count,
					 				'data'=>$proposal_id_list
					 			),
					 		)
					 	);
				}
				else
				{
					array_push($data,
				 		array(
				 			'transaction'=>array(
				 				'count'=>0,
				 				'success_count'=>0
				 			),
				 		)
				 	);
				}
			break;
			
			default:
				$data['status'] = 'error';
			break;
		}
		echo json_encode($data);
	}

	function view_transaction_detail($sla_year,$sla_index)
	{
		$sel_month = $this->input->get_post('month');
		$sel_year = $this->input->get_post('year');

		$url = site_url($this->sla_rule[$sla_year][$sla_index]['url_transaction']). '?month=' .$sel_month. '&year=' .$sel_year;
		$json_data = $this->curl_model->get($url);
		$array_data = json_decode($json_data,true);
		$data['title'] = $this->sla_rule[$sla_year][$sla_index]['name'];
		$data['small_title'] = $this->sla_rule[$sla_year][$sla_index]['target'];
		$data['sla_index'] = $sla_index;
		$data['data'] = $array_data;
		$Data = $this->load->view('sla_report/sla_view_detail',$data,true);

		$data['content'] = $Data;
		$this->load->view('slave_index',$data);
	}


	function excel_export_sla($sel_year)
	{
		ini_set('include_path', 'class/phpExcel');
		include 'PHPExcel.php';
		include 'PHPExcel/Writer/Excel2007.php';
		include 'PHPExcel/IOFactory.php';

		//Config
		$original_filename = 'uploads/sla_report/sla_report_2560.xls';
		$file_name = "sla_export_" .date("Y_m_d_H_i_s"). ".xls";
		$fname = tempnam("/var/www/virtual/nnr.nstda.or.th/htdocs/tmp", $file_name);

		//Reader
		$inputFileType = PHPExcel_IOFactory::identify($original_filename); 
		$objReader = PHPExcel_IOFactory::createReader($inputFileType);
		//$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($original_filename);

		//Writer		
		$objPHPExcel->getProperties()->setCreator("Sakda Chutchawan");
		$objPHPExcel->getProperties()->setLastModifiedBy("Sakda Chutchawan");
		$objPHPExcel->getProperties()->setTitle("Office 2007 XLSX Test Document");
		$objPHPExcel->getProperties()->setSubject("Office 2007 XLSX Test Document");
		$objPHPExcel->getProperties()->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.");


		//Modify Master Data
		foreach ($this->sla_rule[$sel_year] as $sla_index => $sla_data) {
			$iCols=6;
			switch ($sla_index) {
				case '1':
					$iRows = 8;
				break;
				case '2':
					$iRows = 19;
				break;
				case '3':
					$iRows = 30;
				break;
				case '4':
					$iRows = 41;
				break;
				case '5':
					$iRows = 52;
				break;
				default:
					$iRows = 99;
				break;
			}
			#$iRows = $iRows + 80;
			$url_data = $sla_data['url_transaction'];
			for ($iLoop=1;$iLoop<=12;$iLoop++)
			{
				switch ($sla_index) {
					case '1':
					case '2':
					case '3':
					case '4':
					case '5':
						$sla_month = $this->sla_report_model->sla_month_data($sel_year,$iLoop);
						$url = site_url($url_data). '?month=' .$sla_month['month_index']. '&year=' .$sla_month['year_index'];
						$json_data = $this->curl_model->get($url);
						$sla_data = json_decode($json_data,true);
						if (is_array($sla_data))
						{
							$objPHPExcel->getActiveSheet()->SetCellValue(chr($iCols+65+$iLoop). '' .$iRows,$sla_data[0]['transaction']['count']);
							#$objPHPExcel->getActiveSheet()->SetCellValue(chr($iCols+65+$iLoop). '' .$iRows,chr($iCols+65+$iLoop). '' .$iRows);
						}
					break;
					
					default:
						#$objPHPExcel->getActiveSheet()->SetCellValue(chr($iCols+65+$iLoop). '' .$iRows,'0');
						#$objPHPExcel->getActiveSheet()->SetCellValue(chr($iCols+65+$iLoop). '' .$iRows,$sla_index);
					break;
				}
				
			}
			$iRows++;
			$iRows++;

			for ($iLoop=1;$iLoop<=12;$iLoop++)
			{
				switch ($sla_index) {
					case '1':
					case '2':
					case '3':
					case '4':
					case '5':
						$sla_month = $this->sla_report_model->sla_month_data($sel_year,$iLoop);
						$url = site_url($url_data). '?month=' .$sla_month['month_index']. '&year=' .$sla_month['year_index'];
						$json_data = $this->curl_model->get($url);
						$sla_data = json_decode($json_data,true);
						if (is_array($sla_data))
						{
							$objPHPExcel->getActiveSheet()->SetCellValue(chr($iCols+65+$iLoop). '' .$iRows, $sla_data[0]['transaction']['success_count']);
						}

					break;
					
					default:
						#$objPHPExcel->getActiveSheet()->SetCellValue(chr($iCols+65+$iLoop). '' .$iRows,'0');
					break;
				}
				
			}
		}
		


		$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter->save(str_replace('.php', '.xlsx', $fname));
		
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
		header("Content-Type: application/force-download");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		
		//header("Content-Type: application/x-msexcel; name=\"" .$file_name. "\"");
		header("Content-Disposition: attachment; filename=\"" .$file_name. "\"");
		header("Content-Transfer-Encoding: binary ");
		header("Content-Length: ".filesize($fname));
		//$fh=fopen($fname, "rb");
		//fpassthru($fh);
		readfile($fname); 
		unlink($fname);
		exit();

	}
}