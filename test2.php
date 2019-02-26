function get_calendar_custom($catid, $initial = true) {  
    global $wpdb, $m, $monthnum, $year, $wp_locale, $posts; 
    $key = md5( $m . $monthnum . $year ); 
    if ( $cache = wp_cache_get( 'get_calendar_custom', 'calendar_custom' ) ) { 
        if ( isset( $cache[ $key ] ) ) { 
            echo $cache[ $key ]; 
            return; 
        } 
    } 
      
    ob_start(); 
    if ( !$posts ) { 
        $gotsome = $wpdb->get_var("SELECT ID from $wpdb->posts WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1"); 
        if ( !$gotsome ) 
        return; 
    } 
      
    if ( isset($_GET['w']) ) 
        $w = ''.intval($_GET['w']); 
      
    $week_begins = intval(get_option('start_of_week')); 
      
    if ( !empty($monthnum) && !empty($year) ) { 
        $thismonth = ''.zeroise(intval($monthnum), 2); 
        $thisyear = ''.intval($year); 
    } elseif ( !empty($w) ) { 
        $thisyear = ''.intval(substr($m, 0, 4)); 
        $d = (($w - 1) * 7) + 6; 
        $thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('${thisyear}0101', INTERVAL $d DAY) ), '%m')"); 
    } elseif ( !empty($m) ) { 
        $thisyear = ''.intval(substr($m, 0, 4)); 
        if ( strlen($m) < 6 ) 
            $thismonth = '01'; 
        else
            $thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2); 
    } else { 
        $thisyear = gmdate('Y', current_time('timestamp')); 
        $thismonth = gmdate('m', current_time('timestamp')); 
    } 
      
    $unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear); 
      
    $previous = $wpdb->get_row("SELECT DISTINCT MONTH(post_date) AS month, YEAR(post_date) AS year FROM $wpdb->posts LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id) LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) WHERE post_date < '$thisyear-$thismonth-01' AND $wpdb->term_taxonomy.term_id IN ($catid) AND $wpdb->term_taxonomy.taxonomy = 'category' AND post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 1"); 
    $next = $wpdb->get_row("SELECT DISTINCT MONTH(post_date) AS month, YEAR(post_date) AS year FROM $wpdb->posts LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id) LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) WHERE post_date > '$thisyear-$thismonth-01' AND $wpdb->term_taxonomy.term_id IN ($catid) AND $wpdb->term_taxonomy.taxonomy = 'category' AND MONTH( post_date ) != MONTH( '$thisyear-$thismonth-01' ) AND post_type = 'post' AND post_status = 'publish' ORDER BY post_date ASC LIMIT 1"); 
  
    echo '<div id="calendar_wrap"><table id="wp-calendar" summary="' . __('Calendar') . '"> <caption>' . sprintf(_x('%1$s %2$s', 'calendar caption'), $wp_locale->get_month($thismonth), date('Y', $unixmonth))  . '</caption> <thead> <tr>'; 
    $myweek = array(); 
    for ( $wdcount=0; $wdcount<=6; $wdcount++ ) { 
        $myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);  
    } 
    foreach ( $myweek as $wd ) { 
        $day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd); 
        echo "<th abbr=\"$wd\" scope=\"col\" title=\"$wd\">$day_name</th>"; 
    } 
      
    echo ' </tr> </thead><tfoot> <tr>'; 
      
    if ( $previous ) { 
        echo '<td abbr="' . $wp_locale->get_month($previous->month) . '" colspan="3" id="prev"><a href="' . calendar_custom_link(get_month_link($previous->year, $previous->month), $catid) . '" title="' . sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($previous->month), date('Y', mktime(0, 0 , 0, $previous->month, 1, $previous->year))) . '">&laquo; ' . $wp_locale->get_month_abbrev($wp_locale->get_month($previous->month)) . '</a></td>'; 
    } else { 
        echo '<td colspan="3" id="prev">&nbsp;</td>';  
    } 
    echo '<td>&nbsp;</td>'; 
    if ( $next ) { 
        echo '<td abbr="' . $wp_locale->get_month($next->month) . '" colspan="3" id="next"><a href="' . calendar_custom_link(get_month_link($next->year, $next->month), $catid) . '" title="' . sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($next->month), date('Y', mktime(0, 0 , 0, $next->month, 1, $next->year))) . '">' . $wp_locale->get_month_abbrev($wp_locale->get_month($next->month)) . ' &raquo;</a></td>'; 
    } else { 
        echo '<td colspan="3" id="next">&nbsp;</td>';  
    } 
    echo ' </tr> </tfoot><tbody> <tr>'; 
      
    $dyp_sql = "SELECT DISTINCT DAYOFMONTH(post_date) FROM $wpdb->posts LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id) LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) WHERE MONTH(post_date) = '$thismonth' AND $wpdb->term_taxonomy.term_id IN ($catid) AND $wpdb->term_taxonomy.taxonomy = 'category' AND YEAR(post_date) = '$thisyear' AND post_type = 'post' AND post_status = 'publish' AND post_date < '" . current_time('mysql') . "'"; 
    $dayswithposts = $wpdb->get_results($dyp_sql, ARRAY_N); 
    if ( $dayswithposts ) { 
        foreach ( (array) $dayswithposts as $daywith ) { 
            $daywithpost[] = $daywith[0]; 
        } 
    } else { 
        $daywithpost = array();  
    } 
      
    if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'camino') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'safari') !== false) 
        $ak_title_separator = "\n"; 
    else
        $ak_title_separator = ', '; 
      
    $ak_titles_for_day = array(); 
    $ak_post_titles = $wpdb->get_results("SELECT post_title, DAYOFMONTH(post_date) as dom FROM $wpdb->posts " . "LEFT JOIN $wpdb->term_relationships ON($wpdb->posts.ID = $wpdb->term_relationships.object_id) LEFT JOIN $wpdb->term_taxonomy ON($wpdb->term_relationships.term_taxonomy_id = $wpdb->term_taxonomy.term_taxonomy_id) WHERE YEAR(post_date) = '$thisyear' AND $wpdb->term_taxonomy.term_id IN ($catid) AND $wpdb->term_taxonomy.taxonomy = 'category' AND MONTH(post_date) = '$thismonth' AND post_date < '" . current_time('mysql') . "' AND post_type = 'post' AND post_status = 'publish'"); 
    if ( $ak_post_titles ) { 
        foreach ( (array) $ak_post_titles as $ak_post_title ) { 
            $post_title = apply_filters( "the_title", $ak_post_title->post_title ); 
            $post_title = str_replace('"', '&quot;', wptexturize( $post_title )); 
            if ( empty($ak_titles_for_day['day_'.$ak_post_title->dom]) ) 
                $ak_titles_for_day['day_'.$ak_post_title->dom] = ''; 
            if ( empty($ak_titles_for_day["$ak_post_title->dom"]) ) // first one 
                $ak_titles_for_day["$ak_post_title->dom"] = $post_title; 
            else
                $ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . $post_title; 
        } 
    } 
  
    $pad = calendar_week_mod(date('w', $unixmonth)-$week_begins); 
    if ( 0 != $pad ) 
        echo '<td colspan="'.$pad.'" class="pad">&nbsp;</td>'; 
      
    $daysinmonth = intval(date('t', $unixmonth)); 
      
    for ( $day = 1; $day <= $daysinmonth; ++$day ) { 
        if ( isset($newrow) && $newrow ) 
            echo "</tr><tr>"; 
              
        $newrow = false; 
        if ( $day == gmdate('j', (time() + (get_option('gmt_offset') * 3600))) && $thismonth == gmdate('m', time()+(get_option('gmt_offset') * 3600)) && $thisyear == gmdate('Y', time()+(get_option('gmt_offset') * 3600)) ) 
            echo '<td id="today">'; 
        else
            echo '<td>'; 
              
        if ( in_array($day, $daywithpost) ) // any posts today? 
            echo '<a href="' . calendar_custom_link(get_day_link($thisyear, $thismonth, $day), $catid) . "\">$day</a>"; 
        else
            echo $day; 
          
        echo '</td>'; 
        if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) ) 
            $newrow = true;  
    } 
    $pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins); 
    if ( $pad != 0 && $pad != 7 ) 
        echo '<td colspan="'.$pad.'" class="pad">&nbsp;</td>'; 
      
    echo "</tr></tbody></table></div>"; 
    $output = ob_get_contents(); 
    ob_end_clean(); 
    echo $output; 
    $cache[ $key ] = $output; 
    wp_cache_set( 'get_calendar_custom', $cache, 'calendar_custom' ); 
} 
  
function calendar_custom_link($url, $catid){ 
  
 if (isset($catid)){ 
  $url .= strpos($url, '?') === false ? '?' : '&'; 
  $url .= 'cat=' . $catid; 
 } 
  
 return $url; 
} 