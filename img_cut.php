<?php
class ThumbWaterImages{
    /**
     * 生成缩略图/加水印
     * classname ThumbWaterImages
     * datetime:2015-1-15
     * author:chenpz
     *
     * usuage:
     * $th=new ThumbWaterImages(array('imagePre'=>'example', 'imagePath'=>'./uploads/thumb/', 'echoType'=>'file'));
     * $th->setThumb($srcImage, 384, 300, 'prorate');
     */
      
    private $imagepre;              //缩略图名称前缀
    private $imagepath;             //缩略图存放路径
    private $srcImage='';           //源图路径
    private $newImageName;          //缩略图名称
    private $imageType;             //图片类型
    private $echotype;              //输出图片类型，link--直接输出到浏览器；file--保存为文件
    private $im='';                 //临时变量
    private $originName='';         //源图名称
    private $srcW='';               //源图宽
    private $srcH='';               //源图高
    private $errorNum=0;            //错误号
    private $errorMess='';          //用来提供错误报告
     
     
    /**
    *   初始化
    *   @access public
    *   @param  array
    *   @return void
    */
    public function __construct($options = array()){
        $allowOption = array('imagepre','imagepath','echotype');
        foreach($options as $key=>$val){
            $key = strtolower($key);
            //验证成员属性
            if(!in_array($key, $allowOption)){
                $this->errorNum = -3;
                $this->errorMess = $this->getError();
                continue;
            }
            $this->$key = $val;
        }
    }
     
    /**
     *
     * 判断源文件路径、缩略图存放路径是否正确
     * 判断GD库是否支持缩略图格式
     * 初始化图片高宽、缩略图存放路径、缩略图生成方式
     *
     * @access public
     * @param string $srcImage
     * @param int $toW
     * @param int $toH
     * @param string $method   
     *  prorate 按比例缩放/distortion 扭曲型缩图/cut 最小裁剪后的缩图/backFill 背景填充图
     * @param string $echotype
     */
    public function setThumb($srcImage = '', $toW = 0, $toH = 0, $method = 'distortion'){
        $this->srcImage = $srcImage;
        $imageName = explode('/', $this->srcImage);
        $this->originName = $imageName[2];
           //检查源文件是否存在
        if(empty($srcImage) || filetype($srcImage) != 'file'){
            $this->errorNum = 4;
            $this->errorMess = $this->getError();
            return false;
        }
        //检查文件上传路径
        if ($this->echotype == 'file'){
            if(!$this->checkImagePath()){
                $this->errorMess = $this->getError();
                return false;
            }
        }
        $info = '';
        $data = getimagesize($this->srcImage, $info);//获取图像大小
        $this->srcW = $data[0];//宽
        $this->srcH = $data[1];//高
         //检查GD库
        if(!$this->checkGD($data[2])){
            $this->errorMess = $this->getError();
            return false;
        }
        $this->setImageName();//设置缩略图名称
        $toFile = $this->imagepath.$this->newImageName;//缩略图存放路径
        $return = $this->createImageMethod($method, $toFile, $toW, $toH);
        return $return;
    }
     
    /**
     *
     * 初始化缩略图生成方式
     * prorate 按比例缩放
     * distortion 扭曲型缩图
     * cut 最小裁剪后的缩图
     * backFill 背景填充图
     * @param string $method
     * @param string $toFile
     * @param int $toW
     * @param int $toH
     */
    private function createImageMethod($method, $toFile, $toW, $toH){
        switch ($method){
              case 'prorate':
                  $return = $this->prorate($toFile, $toW, $toH);
                  break;
              case 'cut':
                  $return = $this->cut($toFile, $toW, $toH);
                  break;
              case 'backFill':
                  $return = $this->backFill($toFile, $toW, $toH);
                  break;
              default:
                  $return = $this->distortion($toFile, $toW, $toH);
           }
           return $return;
    }
    //生成扭曲型缩图
    function distortion($toFile='', $toW=0, $toH=0){
        $cImg=$this->creatImage($this->im, $toW, $toH, 0, 0, 0, 0, $this->srcW, $this->srcH);
        return $this->echoImage($cImg, $toFile);
        imagedestroy($cImg);
    }
     
    //生成按比例缩放的缩图
    function prorate($toFile, $toW, $toH){
        $toWH = $toW / $toH;
        $srcWH = $this->srcW / $this->srcH;
        if($toWH <= $srcWH){
            $ftoW = $toW;
            $ftoH = $ftoW * ($this->srcH / $this->srcW);
        }else{
              $ftoH = $toH;
              $ftoW = $ftoH * ($this->srcW / $this->srcH);
        }
        if($this->srcW > $toW || $this->srcH > $toH){
            $cImg = $this->creatImage($this->im, $ftoW, $ftoH, 0, 0, 0, 0, $this->srcW, $this->srcH);
            return $this->echoImage($cImg, $toFile);
            imagedestroy($cImg);
        }else{
            $cImg = $this->creatImage($this->im, $this->srcW, $this->srcH, 0, 0, 0, 0, $this->srcW, $this->srcH);
            return $this->echoImage($cImg, $toFile);
            imagedestroy($cImg);
        }
    }
     
    //生成最小裁剪后的缩图
    private function cut($toFile, $toW, $toH){
          $toWH = $toW/$toH;
          $srcWH = $this->srcW / $this->srcH;
          if($toWH <= $srcWH){
               $ctoH = $toH;
               $ctoW = $ctoH * ($this->srcW / $this->srcH);
          }else{
              $ctoW = $toW;
              $ctoH = $ctoW * ($this->srcH / $this->srcW);
          }
        $allImg = $this->creatImage($this->im, $ctoW, $ctoH, 0, 0, 0, 0, $this->srcW, $this->srcH);
        $cImg = $this->creatImage($allImg, $toW, $toH, 0, 0, ($ctoW-$toW) / 2, ($ctoH-$toH) / 2, $toW, $toH);
        return $this->echoImage($cImg, $toFile);
        imagedestroy($cImg);
        imagedestroy($allImg);
    }
  
    //生成背景填充的缩图
    private function backFill($toFile, $toW, $toH, $bk1=255, $bk2=255, $bk3=255){
        $toWH = $toW / $toH;
        $srcWH = $this->srcW / $this->srcH;
        if($toWH <= $srcWH){
            $ftoW = $toW;
            $ftoH = $ftoW * ($this->srcH / $this->srcW);
        }else{
              $ftoH = $toH;
              $ftoW = $ftoH*($this->srcW / $this->srcH);
        }
        if(function_exists("imagecreatetruecolor")){
            @$cImg = imagecreatetruecolor($toW,$toH);
            if(!$cImg){
                $cImg = imagecreate($toW,$toH);
            }
        }else{
            $cImg = imagecreate($toW,$toH);
        }
        $backcolor = imagecolorallocate($cImg, $bk1, $bk2, $bk3);        //填充的背景颜色
        imagefilledrectangle($cImg,0,0,$toW,$toH,$backcolor);
        if($this->srcW > $toW || $this->srcH > $toH){
            $proImg = $this->creatImage($this->im,$ftoW,$ftoH,0,0,0,0,$this->srcW,$this->srcH);
            if($ftoW < $toW){
                 imagecopy($cImg, $proImg, ($toW - $ftoW) / 2, 0, 0, 0, $ftoW, $ftoH);
            }else if($ftoH < $toH){
                 imagecopy($cImg, $proImg, 0, ($toH-$ftoH) / 2, 0, 0, $ftoW, $ftoH);
            }else{
                 imagecopy($cImg, $proImg, 0, 0, 0, 0, $ftoW, $ftoH);
            }
        }else{
             imagecopymerge($cImg, $this->im, ($toW - $ftoW) / 2,($toH - $ftoH) / 2, 0, 0, $ftoW, $ftoH, 100);
        }
        return $this->echoImage($cImg, $toFile);
        imagedestroy($cImg);
    }
    //创建图像
    private function creatImage($img, $creatW, $creatH, $dstX, $dstY, $srcX, $srcY, $srcImgW, $srcImgH){
        if(function_exists("imagecreatetruecolor")){
            @$creatImg = imagecreatetruecolor($creatW, $creatH);
            if($creatImg){
                imagecopyresampled($creatImg, $img, $dstX, $dstY, $srcX, $srcY, $creatW, $creatH, $srcImgW, $srcImgH);
            }else{
                $creatImg=imagecreate($creatW,$creatH);
                imagecopyresized($creatImg, $img, $dstX, $dstY, $srcX, $srcY, $creatW, $creatH, $srcImgW, $srcImgH);
            }
         }else{
            $creatImg=imagecreate($creatW, $creatH);
            imagecopyresized($creatImg, $img, $dstX, $dstY, $srcX, $srcY, $creatW, $creatH, $srcImgW, $srcImgH);
         }
         return $creatImg;
    }
     
    //输出图片，link---只输出，不保存文件。file--保存为文件
    function echoImage($img, $toFile){
        switch($this->echotype){
            case 'link':
                if(function_exists('imagejpeg'))
                    return imagejpeg($img);
                else
                    return imagepng($img);
                break;
            case 'file':
                if(function_exists('imagejpeg'))
                    return imagejpeg($img,$toFile);
                else
                    return imagepng($img,$toFile);
                break;
        }
    }
    /**
     *
     * 设置随机文件名称
     * @access private
     * @return string
     */
    private function setRandName(){
        $fileName = date("YmdHis").rand(100,999);
        return $fileName.'.'.$this->imageType;
    }
    private function setImageName(){
        if ($this->imagepre != ''){
            $this->newImageName = $this->imagepre.'_'.$this->setRandName();
        }else {
            $this->newImageName = $this->setRandName();
        }
    }
    /**
     *
     * 用来检查文件上传路径
     * @access private
     * @return bool
     */
    private function checkImagePath(){
        if(empty($this->imagepath)) {
            $this->errorNum = -2;
            return false;
        }
 
        if(!file_exists($this->imagepath) || !is_writable($this->imagepath)){
            if(!@mkdir($this->imagepath, 0755,true)){
                $this->errorNum = -1;
                return false;
            }
        }
        return true;
    }
    private function checkGD($imageType){
        switch ($imageType){
             case 1:
               if(!function_exists("imagecreatefromgif")){
                       $this->errorNum = 1;
                       return false;
               }
               $this->imageType = 'gif';
               $this->im = imagecreatefromgif($this->srcImage);
               break;
             case 2:
               if(!function_exists("imagecreatefromjpeg")){
                     $this->errorNum = 2;
                     return false;
                }
                $this->imageType = 'jpg';
                $this->im = imagecreatefromjpeg($this->srcImage);   
                break;
             case 3:
                if(!function_exists("imagecreatefrompng")){
                      $this->errorNum = 3;
                      return false;
                 }
                 $this->imageType = 'png';
                 $this->im = imagecreatefrompng($this->srcImage);   
                 break;
              default:
                 $this->errorNum = 0;
                 return false;
          }
          return true;
    }
    /**
     *
     * 用于获取上传后缩略图片的文件名
     * @access public
     * @return string
     */
    public function getNewImageName(){
        return $this->newImageName;
    }
 
    /**
     *
     * 获取上传错误信息
     * @access private
     * @return string
     */
    private function getError(){
        $str='生成缩略图<font color="red">'.$this->originName.'</font>时出错：';
 
        switch($this->errorNum){
            case 4: $str .= '没有找到需要缩略的图片';
                break;
            case 3: $str .= '你的GD库不能使用png格式的图片，请使用其它格式的图片！';
                break;
            case 2: $str .= '你的GD库不能使用jpeg格式的图片，请使用其它格式的图片！';
                break;
            case 1: $str .= '你的GD库不能使用GIF格式的图片，请使用Jpeg或PNG格式！';
                break;
            case -1: $str .= '建立存放缩略图目录失败，请重新指定缩略图目录';
                break;
            case -2: $str .= '必须指定缩略图的路径';
                break;
            case -3: $str .= '初始化参数出错';
                break;
            default: $str .= '末知错误';
        }
 
        return $str.'<br>';
    }
    public function getErrorMsg() {
        return $this->errorMess;
    }
     
     
    /*
        * 功能：PHP图片水印 (水印支持图片或文字)
        * 参数：
        *      $groundImage    背景图片，即需要加水印的图片，暂只支持GIF,JPG,PNG格式；
        *      $waterPos        水印位置，有10种状态，0为随机位置；
        *                        1为顶端居左，2为顶端居中，3为顶端居右；
        *                        4为中部居左，5为中部居中，6为中部居右；
        *                        7为底端居左，8为底端居中，9为底端居右；
        *      $waterImage        图片水印，即作为水印的图片，暂只支持GIF,JPG,PNG格式；
        *      $waterText        文字水印，即把文字作为为水印，支持ASCII码，不支持中文；
        *      $textFont        文字大小，值为1、2、3、4或5，默认为5；
        *      $textColor        文字颜色，值为十六进制颜色值，默认为#FF0000(红色)；
        *
        * 注意：Support GD 2.0，Support FreeType、GIF Read、GIF Create、JPG 、PNG
        *      $waterImage 和 $waterText 最好不要同时使用，选其中之一即可，优先使用 $waterImage。
        *      当$waterImage有效时，参数$waterString、$stringFont、$stringColor均不生效。
        *      加水印后的图片的文件名和 $groundImage 一样。
        */
        public function imageWaterMark($groundImage,$waterPos=0,$waterImage="",$waterText="",$textFont=5,$textColor="#FF0000")
        {
            $isWaterImage = FALSE;
            $formatMsg = "暂不支持该文件格式，请用图片处理软件将图片转换为GIF、JPG、PNG格式。";
 
            //读取水印文件
            if(!empty($waterImage) && file_exists($waterImage))
            {
                $isWaterImage = TRUE;
                $water_info = getimagesize($waterImage);
                $water_w    = $water_info[0];//取得水印图片的宽
                $water_h    = $water_info[1];//取得水印图片的高
 
                switch($water_info[2])//取得水印图片的格式
                {
                    case 1:$water_im = imagecreatefromgif($waterImage);break;
                    case 2:$water_im = imagecreatefromjpeg($waterImage);break;
                    case 3:$water_im = imagecreatefrompng($waterImage);break;
                    default: return false;//die($formatMsg);
                }
            }
 
            //读取背景图片
            if(!empty($groundImage) && file_exists($groundImage))
            {
                $ground_info = getimagesize($groundImage);
                $ground_w    = $ground_info[0];//取得背景图片的宽
                $ground_h    = $ground_info[1];//取得背景图片的高
 
                switch($ground_info[2])//取得背景图片的格式
                {
                    case 1:$ground_im = imagecreatefromgif($groundImage);break;
                    case 2:$ground_im = imagecreatefromjpeg($groundImage);break;
                    case 3:$ground_im = imagecreatefrompng($groundImage);break;
                    default:return false;//die($formatMsg);
                }
            }
            else
            {
                return false;
                //die("需要加水印的图片不存在！");
            }
 
            //水印位置
            if($isWaterImage)//图片水印
            {
                $w = $water_w;
                $h = $water_h;
                $label = "图片的";
            }
            else//文字水印
            {
                $temp = imagettfbbox(ceil($textFont*2.5),0,"./fonts/Eras_Bold_ITC.otf",$waterText);//取得使用 TrueType 字体的文本的范围
                $w = $temp[2] - $temp[6];
                $h = $temp[3] - $temp[7];
                unset($temp);
                $label = "文字区域";
            }
            if( ($ground_w<$w) || ($ground_h<$h) )
            {
                //echo "需要加水印的图片的长度或宽度比水印".$label."还小，无法生成水印！";
                return;
            }
            switch($waterPos)
            {
                case 0://随机
                    $posX = rand(0,($ground_w - $w));
                    $posY = rand(0,($ground_h - $h));
                    break;
                case 1://1为顶端居左
                    $posX = 0;
                    $posY = 0;
                    break;
                case 2://2为顶端居中
                    $posX = ($ground_w - $w) / 2;
                    $posY = 0;
                    break;
                case 3://3为顶端居右
                    $posX = $ground_w - $w;
                    $posY = 0;
                    break;
                case 4://4为中部居左
                    $posX = 0;
                    $posY = ($ground_h - $h) / 2;
                    break;
                case 5://5为中部居中
                    $posX = ($ground_w - $w) / 2;
                    $posY = ($ground_h - $h) / 2;
                    break;
                case 6://6为中部居右
                    $posX = $ground_w - $w;
                    $posY = ($ground_h - $h) / 2;
                    break;
                case 7://7为底端居左
                    $posX = 0;
                    $posY = $ground_h - $h;
                    break;
                case 8://8为底端居中
                    $posX = ($ground_w - $w) / 2;
                    $posY = $ground_h - $h;
                    break;
                case 9://9为底端居右
                    $posX = $ground_w - $w;
                    $posY = $ground_h - $h;
                    break;
                default://随机
                    //$posX = rand(0,($ground_w - $w));
                    //$posY = rand(0,($ground_h - $h));
                    $posX = 5;
                    $posY = 55;
                    break;    
            }
 
            //设定图像的混色模式
            imagealphablending($ground_im, true);
 
            if($isWaterImage)//图片水印
            {
                imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $water_w,$water_h);//拷贝水印到目标文件        
            }
            else//文字水印
            {
                if( !empty($textColor) && (strlen($textColor)==7) )
                {
                    $R = hexdec(substr($textColor,1,2));
                    $G = hexdec(substr($textColor,3,2));
                    $B = hexdec(substr($textColor,5));
                }
                else
                {
                    return false;
                    //die("水印文字颜色格式不正确！");
                }
                imagestring ( $ground_im, $textFont, $posX, $posY, $waterText, imagecolorallocate($ground_im, $R, $G, $B));        
            }
 
            //生成水印后的图片
            @unlink($groundImage);
            switch($ground_info[2])//取得背景图片的格式
            {
                case 1:imagegif($ground_im,$groundImage);break;
                case 2:imagejpeg($ground_im,$groundImage);break;
                case 3:imagepng($ground_im,$groundImage);break;
                default:return false;//die($errorMsg);
            }
 
            //释放内存
            if(isset($water_info)) unset($water_info);
            if(isset($water_im)) imagedestroy($water_im);
            unset($ground_info);
            imagedestroy($ground_im);
        }
 
 
 
    /**
     * 图片大小比例调整
     *
     * @param $filename       图片路径
     * @param $w　             目标宽度
     * @param $h　　           目标高度
     * @param $override       是否覆盖原文件
     * @param $background  是否产生背景, 如果要求产生背景则产生图像是指定的大小，　图片内容居中
     * @param $color            背影色
     */
    public function RatioAdjuct($filename = '', $w = 440, $h = 300, $override = null, $background = null, $color = '0xFFFFFF') {
        list( $imgWidth, $imgHeight ) = getImageSize ( $filename );
         
        $ratioX = $imgWidth / $w;
        $ratioY = $imgHeight / $h;
         
        if ($ratioX > $ratioY || $ratioX == $ratioY) {
            $dst_w = $w;
            $dst_h = ceil ( $imgHeight / $ratioX );
        } else if ($ratioY > $ratioX) {
            $dst_h = $h;
            $dst_w = ceil ( $imgWidth / $ratioY );
        }
         
        //判断图片类型
        switch (strtolower ( strrchr ( $filename, '.' ) )) {
            case '.jpg' :
            case '.jpeg' :
                $im = imageCreateFromJpeg ( $filename );
                break;
            case '.gif' :
                $im = imageCreateFromGif ( $filename );
                break;
             
            case '.png' :
                $im = imageCreateFromPng ( $filename );
        }
         
        //是否有背景色
        if (null !== $background) {
            //将背景色转换为十进制的红绿蓝值
            $dec = hexdec ( $color );
            $red = 0xFF & ($dec >> 0x10);
            $green = 0xFF & ($dec >> 0x8);
            $blue = 0xFF & $dec;
             
            //居中定位并复制
            $dst_pos = array ('d_x' => 0, 'd_y' => 0 );
            ($dst_w == $w) ? ($dst_pos ['d_y'] = (($h - $dst_h) / 2)) : ($dst_pos ['d_x'] = (($w - $dst_w) / 2));
             
            $imBox = imageCreateTrueColor ( $w, $h );
            $color_bg = imageColorAllocate ( $imBox, $red, $green, $blue );
            imageFill ( $imBox, 0, 0, $color_bg );
            imageCopyResized ( $imBox, $im, $dst_pos ['d_x'], $dst_pos ['d_y'], 0, 0, $dst_w, $dst_h, $imgWidth, $imgHeight );
        } else {
            $imBox = imageCreateTrueColor ( $dst_w, $dst_h );
            imageCopyResized ( $imBox, $im, 0, 0, 0, 0, $dst_w, $dst_h, $imgWidth, $imgHeight );
        }
         
        //不替换源图片
        if (null === $override)
            $filename = str_replace ( strrchr ( $filename, '.' ), '', $filename ) . '_thumb.png';
         
        return imagejpeg ( $imBox, $filename ) ? $filename : false;
    }
}
 
 
$th=new ThumbImages(array('imagePre'=>'xxs', 'imagePath'=>'./uploads/thumb/', 'echoType'=>'file'));
$th->imageWaterMark('1034035813_x.jpg', 6, 'sy_small.png');
 
$th->setThumb('1034035813_x.jpg', 400, 400, 'cut');
