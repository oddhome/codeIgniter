<style type="text/css">
	th {
		text-align: center;
	}
</style>

<div class="page-header">
  <h3><?php echo $title; ?><br /><small><?php echo $small_title; ?></small></h3>
</div>


<?php
	switch ($sla_index) {
		case '1':
		case '2':
			$field_id = 'proposal_id';
			$field_name = 'proposal_name';
			$field_date = 'proposal_date';
		break;

		case '3':
		case '4':
			$field_id = 'project_id';
			$field_name = 'project_name';
			$field_date = 'tracking_date';
		break;

		case '5':
			$field_id = 'rtap_project_id';
			$field_name = 'project_name';
			$field_date = 'project_end';
		break;

	}
	$iLoop = 1;
	$Data = '<table class="table table-bordered">';
	$Data .= '<tr>';
	$Data .= '<th>ลำดับ</th>';
	$Data .= '<th>รหัส</th>';
	$Data .= '<th>ชื่อโครงการ</th>';
	$Data .= '<th>วันที่รับ</th>';
	$Data .= '<th>วันที่เป้าหมาย</th>';
	$Data .= '<th>วันที่สำเร็จ</th>';
	$Data .= '<th>จำนวนวัน</th>';
	$Data .= '<th>สถานะ</th>';
	$Data .= '</tr>';
	foreach ($data[0]['transaction']['data'] as $key => $value) {
		$Data .= '<tr>';
		$Data .= '<td width="20" align="center">' .$iLoop. '</td>';
		$Data .= '<td width="140" align="center">' .$value[$field_id]. '</td>';
		$Data .= '<td>' .$value[$field_name]. '</td>';
		$Data .= '<td width="100" align="center">' .$value[$field_date]. '</td>';
		$Data .= '<td width="100" align="center">' .$value['sla_target_date']. '</td>';
		if ($value['sla_response']==date("Y-m-d"))
		{
			$sla_response = 'No Record';
		}
		else
		{
			$sla_response = $value['sla_response'];
		}
		$Data .= '<td width="100" align="center">' .$sla_response. '</td>';
		$Data .= '<td width="80" align="center">' .$value['diff_date']. '</td>';

		if ($value['sla_status']=='true')
		{
			$sla_status = '<span class="label label-success">Success</span>';
		}
		elseif($value['sla_status']=='false')
		{
			$sla_status = '<span class="label label-danger">Fail</span>';
		}
		else
		{
			$sla_status = '<span class="label label-warning">Wait</span>';
		}

		$Data .= '<td width="50" align="center">' .$sla_status. '</td>';
		$Data .= '</tr>';
		$iLoop++;
	}
	$Data .= '</table>';
	echo $Data;
?>