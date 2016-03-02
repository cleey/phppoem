<?php
namespace poem\more;
class page{
	
	function run($m,$url='',$affix='',$page_size=15,$show_nums=5){
		$page = intval( I('p')) ? intval( I('p')) : 1;
		$tm  = clone $m;
		$total = $tm->count(); // 总记录数
		$list = $m->limit( ($page-1)*$page_size ,$page_size )->select();  // 结果列表
		$info['total'] = $total; // 总记录数
		$info['np'] = $page;  // 当前页
		$info['tp'] = ceil((int)$info['total']/(int)$page_size);  //总页数
		$info['url'] = $url;  //url

		$info['list'] = $list;
		$info['page'] = $page;
		$info['html'] = $this->pagehtml($page,$info['tp'],$affix,$url,$show_nums);
		return $info;
	}

	// $np 当前页 $tp 总页数
	function pagehtml($np,$tp,$affix,$url,$num=5){
		$up	 = $np-1;   // 上一页
		$dp  = $np+1;   // 下一页
		$f 	 = ($np == 1)?'disabled':'';   // 是否为首页
		$e 	 = ($np == $tp)?'disabled':'';  // 是否问尾页
		$html = '';
		if( $tp > 0){
			$html .= '<ul class="pagination">';
			$html .= "<li> <span>共 $total 条 </span> </li>";
			$html .= "<li> <span>当前 $np / $tp 页</span> </li>";
			if($np !=1){
				$html .= "<li class='{$f}'><a href='$url/p/1$affix'> << </a></li>";
				$html .= "<li class='{$f}'><a href='$url/p/$up$affix'> < </a></li>";
			}
			$sep = floor($num/2);
			$begin = 1;
			if( $tp >= $num ){
				if($np > $sep && $np < ($tp - $sep) ){ $begin = $np - $sep;}
				else if($np >= ($tp - $sep) ){ $begin = $tp - $num + 1; }
			}else{
				$num = $tp;
			}
			$sum = 0;
			for ($i=$begin; $i < $num+$begin; $i++) { 
				$cp = ($np == $i) ? 'class="active"':''; //'.$cp.'
				$tu = ($np == $i) ? 'javascript:void(0);' : $url."/p/$i$affix";
				$html .= "<li $cp><a href='$tu'>$i</a></li>";
			}
			if($np != $tp){
				$html .= "<li class='{$e}'><a href='{$url}/p/{$dp}{$affix}'> > </a></li>";
				$html .= "<li class='{$e}'><a href='{$url}/p/{$tp}{$affix}'> >> </a></li>";
			}
			$html .= "</ul>";
		}
		return $html;
	}

}