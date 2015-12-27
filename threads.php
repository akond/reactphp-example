<?php



class workerThread extends Thread {
public function __construct($i){
  $this->i=$i;
}

public function run(){
$opts = array(
  'http'=>array(
  'method'=>"GET",
  'header'=>"Accept-language: en\r\n" .
  "Cookie: foo=bar\r\n"
  )
);
                          
$context = stream_context_create($opts);
                          
$x = file_get_contents('http://10.0.0.1:6789/micro-service.php', false, $context);
echo $x;

}
}



for($i=0;$i<3;$i++){
$workers[$i]=new workerThread($i);
$workers[$i]->start();
}
