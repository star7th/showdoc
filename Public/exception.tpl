<?php
    echo json_encode(
    	array(
    		"error_code"=>999,
    		"error_message" =>strip_tags($e['message'])." 。错误位置：".$e['file']."  ".$e['line'] 
    		)
    )
?>