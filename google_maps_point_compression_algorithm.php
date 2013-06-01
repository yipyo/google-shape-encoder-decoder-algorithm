<?
ini_set('display_errors','On');
error_reporting(E_ALL);


class Google_CodePoints{

private $latkeys = array(1,2,3,4,5,6,7,8,9,'0','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','#','$','%','&','^','(',')','*','+','_','-','.','/',':',';','<','=','>','?','|','"','@','`','{','}','~','[',']','!1','!2','!3','!4','!5','!6','!7','!8','!9');
private $lngkeys = array(1,2,3,4,5,6,7,8,9,'0','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','#','$','%','&','^','(',')','*','+','_','-','.','/',':',';','<','=','>','?','|','"','@','`','{','}','~','[',']','!1','!2','!3','!4','!5','!6','!7','!8','!9');
private $encodekeys = array(' ',1,2,3,4,5,6,7,8,9,'0','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z','#','$','%','&','^','(',')','*','+','_','-','.','/',':',';','<','=','>','?','|','"','@','`','{','}','~','[',']','!1','!2','!3','!4','!5','!6','!7','!8','!9');
private $pattern = '/[+-]?[0-9]{1,3}\./'; //grabs the coordinate base ex: 34. || -117.
public  $latkey, $lngkey, $shapes, $scope, $encoded, $decoded, $error;

    public function __construct($coordinates=null){
       // encode if passed coordinates
       if($coordinates){ 
            $this->setShapes($coordinates); if($this->error){ var_dump($this->error); exit; }
            $this->setScope();              if($this->error){ var_dump($this->error); exit; }
            $this->encodeShapes();
       }
    }

private function setShapes($coordinates){
    if(preg_match("/[abdefghijklmnoqstuvwxyz\/'\\\]+/i",$coordinates)) return $this->error[] = 'invalid character sent in coordinate string';            
    $decimal = 6;  // shave decimal point to length of 
    $coordinates = preg_replace("/[() ]+/",'',$coordinates);
    $shapes = explode('!',$coordinates);
    if(!$shapes[count($shapes)-1]) array_pop($shapes); 
    foreach($shapes as $shape){
        $shape = preg_replace("/[,]{2,}/",'',$shape);
        $shape = preg_replace("/^[,]+/",'',$shape);
        if(empty($shape)) continue;
          $type = substr($shape,0,1);
          $p='';
          $p = substr($shape,2);
          $p = explode(',',$p);
          if($type=='p'){ if(count($p)<6){ continue; } }  // polygon doesn't have at least 3 sides skip
          $poly='';
          $chunk = ($type=='p' || $type=='r') ? 2 : 3; //p/r else c
                    
                    foreach (array_chunk($p, $chunk) as $point) {
                        $lat = explode('.',$point[0]);
                        $lat[1] = substr($lat[1],0,$decimal);  
                        $lat = implode('.',$lat); 
                        $lng = explode('.',$point[1]);
                        $lng[1] = substr($lng[1],0,$decimal);
                        $lng = implode('.',$lng);
                        $poly .= $lat.','.$lng.',';
                        if($type=='c'){
                           $rad = explode('.',$point[2]);
                           if(is_array($rad)){ $rad = $rad[0]; }
                           $poly.= $rad.',';
                        }
                    }
                    
         if($type=='p'){ $this->shapes->p[] = substr($poly,0,-1); $this->shapes->p = array_unique($this->shapes->p); }
         elseif($type=='r'){ $this->shapes->r[] = substr($poly,0,-1); $this->shapes->r = array_unique($this->shapes->r); }
         elseif($type=='c'){ $this->shapes->c[] = substr($poly,0,-1); $this->shapes->c = array_unique($this->shapes->c); }
    }
}

public function setScope($bounds=null){
   // bounds being the db result obj ->hi_lat, etc. on Google_Maps
   if($bounds){ 
       $this->scope['lat'][0] = $bounds->hi_lat;
       $this->scope['lat'][1] = $bounds->lo_lat;
       $this->scope['lng'][0] = $bounds->hi_lng;
       $this->scope['lng'][1] = $bounds->lo_lng;
       $this->scope['diff'][0] = $bounds->diff_lat;
       $this->scope['diff'][1] = $bounds->diff_lng;
       $this->latkey = $bounds->lat_key;
       $this->lngkey = $bounds->lng_key;
        array_splice($this->latkeys, ceil($this->latkey-$this->scope['lat'][1]), 0, array(' ')); // latkey on latkeys array
        array_splice($this->lngkeys, ceil($this->lngkey-$this->scope['lng'][1]), 0, array(' ')); // lngkey on lngkeys array
     return true;
   }
   // if null set scope in class
   foreach($this->shapes as $type => $shape){
      foreach($shape as $s){
         
         if(!isset($firstShape)){ $firstShape=$s; } 
         if($type=='c'){
               $s = explode(',',$s);
               $deg = number_format(($s[2] / 111000),6,'.','');
               $latitudes[]  = $s[0]+$deg;
               $latitudes[]  = $s[0]-$deg;
               $longitudes[] = $s[1]+$deg;
               $longitudes[] = $s[1]-$deg;
         }
         else{
               $s = explode(',',$s);
               foreach (array_chunk($s, 2) as $point) {
                  $latitudes[]  = $point[0];
                  $longitudes[] = $point[1];
               }
         }
      }
   }                    
      sort($latitudes); sort($longitudes); // set highest to lowest for scope
      $this->scope['lat'] = array($latitudes[count($latitudes)-1],$latitudes[0]);
      $this->scope['lng'] = array($longitudes[count($longitudes)-1],$longitudes[0]);
      $this->scope['diff'] = array(number_format($latitudes[count($latitudes)-1] - $latitudes[0],0,'',''),number_format($longitudes[count($longitudes)-1] - $longitudes[0],0,'',''));
      if($this->scope['diff'][1]>99) return $this->error[] = 'Please Contact Us For Service Areas This Big.';
   
   //grabs lat & lng matches of first shape & splices ' ' separately for lat / lng keys 
   preg_match_all($this->pattern,$firstShape,$match); 
   $this->latkey = $match[0][0];  
   $this->lngkey = $match[0][1];
   array_splice($this->latkeys, ceil($this->latkey-$this->scope['lat'][1]), 0, array(' ')); //+1 because error with under 1
   array_splice($this->lngkeys, ceil($this->lngkey-$this->scope['lng'][1]), 0, array(' ')); 
}

private function encodeShapes(){
   foreach($this->shapes as $type => $shape){
    foreach($shape as $s){
    $chunk = ($type == 'c') ? 3 : 2;
    foreach (array_chunk(explode(',',$s), $chunk) as $point) {       
        $lat = explode('.',$point[0]);
        $lat[1] = $this->digicode($lat[1]);
        $lng = explode('.',$point[1]);
        $lng[1] = $this->digicode($lng[1]);
        if(!isset($first)){
           $lat = implode($lat);
           $lng = implode($lng); 
           $first = true;
        }
        else{
            $index = array_search(' ',$this->latkeys);
            // 35 - 36
            $lat[0] = $this->latkeys[($lat[0] - $this->latkey) + $index];
            $lat = implode($lat);
            $index = array_search(' ',$this->lngkeys);
            $lng[0] = $this->lngkeys[($lng[0] - $this->lngkey) + $index];
            $lng = implode($lng);
        }
   $this->encoded .= $lat.$lng;   
      if($type=='c'){
         $rad = explode('.',$point[2]);
         if(is_array($rad)){ $rad = $rad[0]; }
         $this->encoded .= '!!'.$this->digicode($rad);;
      }
    }
    $this->encoded .= ',';
    }
   }
   $this->encoded = substr($this->encoded,0,-1);
}     

public function decode($encoded){
   $eShapes = explode(',',$encoded);
   foreach($eShapes as $eShape){   
      $count=0; $build=$p=$str='';
         if(!isset($first)){
            $block = $eShape; $first=true;
            for($i=0;$i<2;$i++){
                if($i===0){ $key = $this->latkey; $str=$block; }
                else{ $key = $this->lngkey; }
                $str = preg_replace('/'.substr($key,0,-1).'/','',$str,1);
                  $str=str_split($str);          
                  for($s=0;$s<count($str);$s++){
                     if($str[$s]=='!'){ $build.=$str[$s]; }
                     else { $build.=$str[$s]; $count++; }
                     array_shift($str); $s--;
                     if($count===3){
                        $p[] = $key.$this->digiDecode($build);
                        $str = implode($str); $build=''; $count=0;
                        break;
                     }
                  } 
            }
         }// end of first lat / lng point
      $str = (!empty($str)) ? $str : $eShape;
      $str=str_split($str); $block='';
  
     // get the block
      for($i=0;$i<count($str);$i++){
         if($str[$i].@$str[$i+1]=='!!'){
            $str = implode($str);
            $str = explode('!!',$str);
            $rad = $this->digiDecode($str[1]);
            if($rad[0]=='0'){ $rad = substr($rad,1); }
            break;
         }
         if($build==''){
            // toggle lat / lng
            if(!isset($left)){ $key = $this->latkey; $keyS=$this->latkeys; $left=true; }
            else{ $key = $this->lngkey; $keyS=$this->lngkeys; unset($left); }         
            if($str[$i]=='!'){ $val = $str[$i].$str[$i+1]; $i++; } else{ $val =$str[$i]; }
               $build .=  ($key + (array_search($val,$keyS) - array_search(' ',$keyS))).'.'; continue; 
         }
         $block.= $str[$i]; if($str[$i]!='!'){ $count++; }
         if($count===3){
            $build .=  $this->digiDecode($block);
            $p[] = $build; $count=0; $build=$block='';
         }
      }

   if(count($p)==4){ $type ='r'; } elseif(isset($rad)){ $type = 'c'; $p[]=$rad; unset($rad); } else { $type='p'; }
   $this->decoded .= $type.','.implode(',',$p).'!';
   }//foreach
   return substr($this->decoded,0,-1);
}

// class methods
private function digicode($str){
    $str = str_split($str,2); $encoded='';
   // if strlen 1 not 2, causes scalling issue w/ radius. implode 0.$str to make each segmant 2 digit. This is the 0nly occurence found.
   if(strlen($str[count($str)-1])===1){
      $str = implode($str); $str = '0'.$str;
      $str = str_split($str,2);
   }
    foreach($str as $s){
        if($s[0] === '0'){ $encoded[] = $this->encodekeys[$s[1]]; }
        else{ $encoded[] = $this->encodekeys[$s]; }
    }
    return implode($encoded);
}
private function digiDecode($str){
   $s = str_split($str); $decode='';
   for($i=0;$i<count($s);$i++){
      if(is_numeric($s[$i]) && @$s[$i-1] == '!' ): // for 90's
         $decode.= array_search('!'.$s[$i],$this->encodekeys); 
      elseif(is_numeric($s[$i]) && @$s[$i-1] != '!' && $s[$i] != 0): // for 1's
         $decode.= '0'.$s[$i];  
      else:
         $val = array_search($s[$i],$this->encodekeys);
         $decode.= ($val===0) ? '00' : $val; // for ' '  == caused a off by 1! mind your types
      endif;
   }
  return $decode;
}
     
} // end class
?>
