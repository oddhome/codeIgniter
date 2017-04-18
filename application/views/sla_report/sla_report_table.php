<style type="text/css">
	th {
		text-align: center;
	}

	.table-responsive {
	    max-width: 1200px;
	    max-height: 400px;
	    overflow-x: scroll;
	    overflow-y: scroll;
	}
</style>
<div style="text-align: right;">
	<a href="<?php echo site_url('sla_report/excel_export_sla/' .$sel_year); ?>">
		<button class="btn btn-info"><span class="fa fa-file-excel-o"></span> Export Excel</button>
	</a>
</div>
<br />
<?php
	echo '<div class="panel panel-default">';
	echo '<div class="panel-body table-responsive">';
	echo '<table class="table table-bordered">';
	echo '<tr>';
	echo '<th>ลำดับ</th>';
	echo '<th>การบริการที่ส่งมอบ<br />(Service Deliverable)</th>';
	echo '<th>ระดับมาตรฐานการให้บริการ<br />(Service Level)</th>';
	for ($iLoop=1;$iLoop<=12;$iLoop++)
	{
		$sla_month = $this->sla_report_model->sla_month_data($sel_year,$iLoop);
		echo '<th>' .$sla_month['month_name']. '</th>';
	}
	echo '</tr>';
	$rowspan = 8;
	$transaction = array();
	$achievement = array();
	$sum_transaction = array();
	$sum_achievement = array();
	foreach ($sla_rule as $sla_index => $sla_data) {
		echo '<tr>';
		echo '<td rowspan="' .$rowspan. '" width="20" align="center">' .$sla_index. '</td>';
		echo '<td rowspan="' .$rowspan. '" width="400">' .$sla_data['name']. '</td>';
		echo '<td rowspan="' .$rowspan. '" width="250">' .$sla_data['target']. '</td>';
		$url_data = $sla_data['url_transaction'];
		for ($iLoop=1;$iLoop<=12;$iLoop++)
		{
			echo '<td width="140">Transaction :</td>';
		}
		echo '</tr>';
		echo '<tr>';
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
					#echo $url;
					$json_data = $this->curl_model->get($url);
					$sla_data = json_decode($json_data,true);
					if (is_array($sla_data))
					{
						if ($iLoop==1)
						{
							$sum_transaction[$sla_index][$iLoop] = $sla_data[0]['transaction']['count'];
						}
						else
						{
							if (isset($sum_transaction[$sla_index][$iLoop-1]))
							{
								$old_transaction = $sum_transaction[$sla_index][$iLoop-1];
							}
							else
							{
								$old_transaction = 0;
							}
							$sum_transaction[$sla_index][$iLoop] = $old_transaction + $sla_data[0]['transaction']['count'];
						}
						$transaction[$sla_index][$iLoop] = $sla_data[0]['transaction']['count'];
						echo '<td width="140" align="center"><a href="' .site_url('sla_report/view_transaction_detail/' .$sel_year. '/' .$sla_index. ''). '?month=' .$sla_month['month_index']. '&year=' .$sla_month['year_index']. '" class="hs_view">' .$sla_data[0]['transaction']['count']. '</a></td>';
					}
					else
					{
						$transaction[$sla_index][$iLoop] = 0;
						echo '<td width="140" align="center">0</td>';
					}
				break;
				
				default:
					echo '<td width="140" align="center">0</td>';
				break;
			}
			
		}
		echo '</tr>';
		echo '<tr>';
		for ($iLoop=1;$iLoop<=12;$iLoop++)
		{
			$sla_month = $this->sla_report_model->sla_month_data($sel_year,$iLoop);
			echo '<td width="140">Achievement :</td>';
		}
		echo '</tr>';
		echo '<tr>';
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
						$achievement[$sla_index][$iLoop] = $sla_data[0]['transaction']['success_count'];
						if ($iLoop==1)
						{
							$sum_achievement[$sla_index][$iLoop] = $sla_data[0]['transaction']['success_count'];
						}
						else
						{
							if (isset($sum_achievement[$sla_index][$iLoop-1]))
							{
								$old_sum_achivement = $sum_achievement[$sla_index][$iLoop-1];
							}
							else
							{
								$old_sum_achivement = 0;
							}
							$sum_achievement[$sla_index][$iLoop] = $old_sum_achivement + $sla_data[0]['transaction']['success_count'];
						}
						echo '<td width="140" align="center">' .$sla_data[0]['transaction']['success_count']. '</td>';
					}
					else
					{
						$achievement[$sla_index][$iLoop] = 0;
						echo '<td width="140" align="center">0</td>';
					}
				break;
				
				default:
					echo '<td width="140" align="center">0</td>';
				break;
			}
			
		}
		echo '</tr>';
		echo '<tr>';
		for ($iLoop=1;$iLoop<=12;$iLoop++)
		{
			$sla_month = $this->sla_report_model->sla_month_data($sel_year,$iLoop);
			echo '<td width="140">% Achievement :</td>';
		}
		echo '</tr>';
		echo '<tr>';
		for ($iLoop=1;$iLoop<=12;$iLoop++)
		{
			switch ($sla_index) {
				case '1':
				case '2':
				case '3':
				case '4':
				case '5':
					if ($transaction[$sla_index][$iLoop]>0)
					{
						$percent_achievement = ($achievement[$sla_index][$iLoop]*100)/$transaction[$sla_index][$iLoop];
					}
					else
					{
						$percent_achievement = 0;
					}
					echo '<td width="140" align="center">' .number_format($percent_achievement,2,'.',','). '</td>';
				break;
				
				default:
					echo '<td width="140" align="center">0.00</td>';
				break;
			}
			
		}
		echo '</tr>';
		echo '<tr>';
		for ($iLoop=1;$iLoop<=12;$iLoop++)
		{
			$sla_month = $this->sla_report_model->sla_month_data($sel_year,$iLoop);
			echo '<td width="140">% Cummulative :</td>';
		}
		echo '</tr>';
		echo '<tr>';
		for ($iLoop=1;$iLoop<=12;$iLoop++)
		{
			#print_r ($transaction);
			#echo '<hr />';
			switch ($sla_index) {
				case '1':
				case '2':
				case '3':
				case '4':
				case '5':
					#print_r ($sum_achievement[$sla_index][$iLoop]);
					#echo '<hr />';
					if (isset($sum_transaction[$sla_index][$iLoop]))
					{
						if ($sum_transaction[$sla_index][$iLoop]==0)
						{
							$percent_commulative = 0;
						}
						else
						{
							$percent_commulative = ($sum_achievement[$sla_index][$iLoop]/$sum_transaction[$sla_index][$iLoop])*100;
						}
					}
					else
					{
						$percent_commulative = 0;
					}
					echo '<td width="140" align="center">' .number_format($percent_commulative,2,'.',','). '</td>';
				break;
				
				default:
					echo '<td width="140" align="center">0.00</td>';
				break;
			}
			
		}
		echo '</tr>';
	}
	echo '</table>';
	echo '</div>';
	echo '</div>';
?>