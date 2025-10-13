<?php
$zip='C:\\Users\\EverZr\\AppData\\Local\\Temp\\excel_68ed23a88f3c5.xlsx';
$z=new ZipArchive();
if($z->open($zip)===true){
    for($i=0;$i<$z->numFiles;$i++){
        echo $z->getNameIndex($i).PHP_EOL;
    }
    $z->close();
} else {
    echo 'failed to open ' . $zip . PHP_EOL;
}
