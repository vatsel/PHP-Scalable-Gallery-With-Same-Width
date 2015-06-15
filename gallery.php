<?php
	echo('<head>');
	
	$currentURI = $_SERVER['REQUEST_URI']; 
	$images = array();
	$portraits = array();
	$landscapes = array(); 
  $contentWidth = 96; //in VW
  $portWidth=$contentWidth/2;
	$galleryImageMargins = 2;//in px
  $maxWidth = 1400; //in px
  $minWidth = 350; //in px
  $maxHeight = 50; //in vw
  
	//class contains all basic attributes & calculates ratio and type (landscape vs portrait) & adds image to correct array
	class ImageClass{
    public $maxHeight; //pixels
    public $maxWidth; //pixels
    public $url;
    public $ratio;
    function __construct($widthIn,$heightIn,$urlIn){  
      $this->maxHeight = $heightIn;
      $this->maxWidth = $widthIn;
      $this->url = $urlIn;
	      if ($this->maxWidth > $this->maxHeight){
	      $this->is_landscape=true;
	      $this->ratio = $this->maxWidth / $this->maxHeight;
	      global $landscapes, $landN;
	      $landscapes[] = $this;
      } else {
	      $this->is_landscape=false;
	      $this->ratio = $this->maxHeight / $this->maxWidth;
	      global $portraits, $portN;
	      $portraits[] = $this;
      }
    }
	}
  
	class HorizontalBlock{ 
	  public $image1;
    public $image2;
    public $blockRatio; // measured W/H, unllike the images which are measured H/W.
    function __construct($image1in,$image2in){
		  $this->image1 = $image1in;
      $this->image2 = $image2in;
      $this->blockRatio = 1/$this->image1->ratio + 1/$this->image2->ratio;
	  }
	}
  
	//custom sorting f() for ImageClass by ratios ; large to small (reversed for pop)
	function compareRatios($img1, $img2){
	  $r1 = $img1->ratio;
	  $r2 = $img2->ratio;
	  if ($r1 == $r2) return 0;   
	  return ($r1 > $r2) ? 1 : -1;    
	}
  
	//get all images and create ImageClass instances for them
	$i=0;
	foreach(glob("gallery/*".jpg) as $imageN){
		$imageUrl= "http://www.vatsel.com".$currentURI.$imageN;
		list($width,$height)=getImagesize($imageUrl);
		$image = new ImageClass($width,$height,$imageUrl); 
	}
	uasort($landscapes,'compareRatios');
        
  //Create output for CSS & echo()
  $galleryOutput = array();
  if (count($landscapes) * 2 > count($portraits)){
	  $galleryOutput[] = array_pop($landscapes);
    $lastWasP = False;
    while(count($landscapes) > 0){
      if(!$lastWasP && count($portraits) > 1){ 
        $galleryOutput[] = new HorizontalBlock(array_pop($portraits),array_pop($portraits));
        $lastWasP = True;
      }else{ 
        $galleryOutput[] = array_pop($landscapes); 
        $lastWasP = False;
      }
    }
    if(count($portraits) > 0) $galleryOutput[] = array_pop($portraits);
	}elseif(count($landscapes) * 2 > count($portraits)-2){
	  $galleryOutput[] = new HorizontalBlock(array_pop($portraits),array_pop($portraits));
    while(count($landscapes)>0){
      $galleryOutput[] = array_pop($landscapes);
      if(count($portraits)>1){
        $galleryOutput[] = new HorizontalBlock(array_pop($portraits),array_pop($portraits));
      }elseif (count($portraits)>0){
        $galleryOutput[] = array_pop($portraits);
      }
    } 
	}else{
	  $galleryOutput[] = new HorizontalBlock(array_pop($portraits),array_pop($portraits));
    while(count($landscapes)>0){
      $galleryOutput[] = array_pop($landscapes);
      if(count($portraits)>1){
        $galleryOutput[] = new HorizontalBlock(array_pop($portraits),array_pop($portraits));
      }elseif(count($portraits)>0){
        $galleryOutput[] = array_pop($portraits);
      }
    }
    while(count($portraits)>0){
      if(count($portraits)>1){
        $galleryOutput[] = new HorizontalBlock(array_pop($portraits),array_pop($portraits));
      }else{
        $galleryOutput[] = array_pop($portraits);
      }
    }
	}
    
	require '../templates/head.php';
  
  echo('<style>'); 
	$i = 0;		
	foreach($galleryOutput as $item){
		if($item instanceof HorizontalBlock){
			$height = $contentWidth/$item->blockRatio;
      $maxW1 = $maxWidth/$item->blockRatio/$item->image1->ratio;
      $maxW2 = $maxWidth - $maxW1;
      $maxH = $maxW1 * $item->image1->ratio;
      $minH1 = $minWidth * $item->image1->ratio;
      $minH2 = $minWidth * $item->image2->ratio;
			echo('        
        #galleryImage'.$i++.'{height: '.$height.'vw; width: '.$height/$item->image1->ratio.'vw; min-height:'.$minH1.'px;  min-width:'.$minWidth.'px; max-width:'.$maxW1.'px; max-height:'.$maxH.'px; margin: '.$galleryImageMargins.'px ;}
        #galleryImage'.$i++.'{height: '.$height.'vw; width: '.$height/$item->image2->ratio.'vw; min-height:'.$minH2.'px;  min-width:'.$minWidth.'px; max-width:'.$maxW2.'px; max-height:'.$maxH.'px; margin: '.$galleryImageMargins.'px ;}'
			);
		}elseif($item instanceof ImageClass){
			echo('#galleryImage'.$i++.'{');
			if( $item->maxWidth > $item->maxHeight){
				echo('margin: '.$galleryImageMargins.'px  auto '.$galleryImageMargins.'px auto; width: '.$contentWidth.'vw; max-width:'.$maxWidth.'px;');
			}else{			
        $max = $maxWidth/2;
				echo('margin: '.$galleryImageMargins.'px;width: '.$portWidth.'vw; max-width:'.$max.'px;');
			}
			echo('}
      ');
		}else{
			throw new Exception('Supplied element for CSS generation is not a HorizontalBlock or an ImageClass type.');
		}
	}
  echo('</style>');
    echo('</head>
	<body>');
  
  // =============================== BODY ===================================
  
  
	require '../templates/nav.php';  
	echo('<div id="gallery_content">');
  $i = 0;  
	foreach($galleryOutput as $item){  
    echo('<div class="horizontalBlock">');
    if($item instanceof HorizontalBlock){
      echo('<img id=galleryImage'.$i++.' src='.$item->image1->url.'></img>');
      echo('<img id=galleryImage'.$i++.' src='.$item->image2->url.'></img>');
    }elseif($item instanceof ImageClass){
       echo('<img id=galleryImage'.$i++.' src='.$item->url.'></img>');
    }else{
			throw new Exception('Supplied element for HTML body generation is not a HorizontalBlock or an ImageClass type.');
		}
    echo('</div>');
  }    
  echo('<div id=back_to_main><a href="http://vatsel.com">Back to main page</a></div>
  </div>');
  
  
	require '../templates/footer.php';  
	  
	echo('</body>');
?>