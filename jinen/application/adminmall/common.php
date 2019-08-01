<?php

function back($status=200,$message='',$data=''){
    return ['status' => $status, 'msg' => $message, 'data' => $data];
}