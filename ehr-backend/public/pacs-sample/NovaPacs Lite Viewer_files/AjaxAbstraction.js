/************************************************************************************************
*NOTES: whereas the reference to the DOM event used to use the IE specific event name, 
*       in this case 'onmousedown', the designers of ASP.Net Ajax have now opted to use 
*       the naming convention adopted by Firefox and Safari
**************************************************************************************************/

//TODO - search and replace all tags




/*****************************************************
* Global Objects
******************************************************/
Series.prototype = new Study();
Image.prototype = new Series(false);		//Derived from Series - javascripts way of inheritance
var image = new Image(false); //Image object
var measure = new Measure(false); //Measure object
var studyBrowser = new StudyBrowser(false); //StudyBrowser object
var imageCache = new ImageCache(false);  //Image Cache
var layOut = new LayOut(false);
var zoom = new Zoom(false);
var report = new Report(false);
var mouse = new MouseEvents(false);
var windowLevel = new WindowLevel(false);
var seriesNavigationToolBar = new SeriesNavigationToolbar(false);
var comparison = new Comparison();
var toolbar = new ToolBar(false);
var imagePaging = new Paging(false);
var annotation = new VoiceAnnotation(false);

//comparison objects
Series.prototype = new Study();
Image.prototype = new Series(true);		//Derived from Series - javascripts way of inheritance
var imageComp = new Image(true); //Image Comparision Object
var studyBrowserComp = new StudyBrowser(true);
var imageCacheComp = new ImageCache(true);  //Image Cache
var measureComp = new Measure(true); //Measure object
var layOutComp = new LayOut(true);
var zoomComp = new Zoom(true);
var reportComp = new Report(true);
var mouseComp = new MouseEvents(true);
var windowLevelComp = new WindowLevel(true);
var seriesNavigationToolBarComp = new SeriesNavigationToolbar(true);
var toolbarComp = new ToolBar(true);
var imagePagingComp = new Paging(true);


var mouseMovementsInterval = null;
var resizeTimerID = null;




//Global Variables
var comparisonMode = false;
var parentDiv;
var activeTool = false;
var currentHelpTopic= 'helpToolMenu';
var resizeWindow = true;
var ThumbNailWidth = 50;
var ThumbNailHeight = 201;
var leftMargin = 60;
var rightMargin = 60;
var topMargin = 40;
var divPadding = 20;
var checkSessionInterval = 30000;  // 5 minutes = 5 * 6000 mil seconds
var previousImg=null;
var reloadWinLevelOptions = true; //used to determine it the reloading of the dropdown should occurr on an asynchronous postback
var _activeSeriesScrolling = null;
var _CurrentScrollingImg
var IE_Version = null;





/*****************************************************
* Voice annotations Class Object
******************************************************/
function VoiceAnnotation(){
	var _isComparison = false;
	var _imageNum = null;
	var _id = 'AnnotationDiv';
	var _pointerID ='annotationPointer';
	var _parentDomElement = 'form';
	var _height=0;
	var _width =0;
	var _imageNumber = null;
	var _audioAndMouseCoordinates = new Array()
	var _pixelSpacingX = 1;
	var _pixelSpacingY = 1;
	var _resizeRatio =1;
	var _previousMouseEvent = 0;
	var _nextMouseEvent = 0;
	var _creationDate = new Array();
	var _selectedTab =0;
	
	
	
	
	//--------properties--------
	
	this.get_PointerID = function(){
		return _pointerID;
	}
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_ID = function(){
		return _id;
	}
	this.get_ParentDomElement = function(){
		return _parentDomElement;
	}
	this.set_Height = function(value){
		_height = value;
		$get(_id).style.height = _height + 'px';
	}
	this.get_Height = function(){
		return _height;
	}
	this.set_Width = function(value){
		_width = value;
		$get(_id).style.width = _width + 'px';
	}
	this.get_Width = function(){
		return _width;
	}
	this.set_ImageNumber = function(value){
		_imageNumber = value;
	}
	this.get_ImageNumber = function(){
		return _imageNumber;
	}
	this.set_AudioAndMouseCoordinates = function(value){
		_audioAndMouseCoordinates = value;
	}
	this.get_PixelSpacingX = function(){
		return _pixelSpacingX;
	}
	this.set_PixelSpacingX = function(value){
		_pixelSpacingX = value;
	}
	this.get_PixelSpacingY = function(){
		return _pixelSpacingY;
	}
	this.set_PixelSpacingY = function(value){
		_pixelSpacingY = value;
	}
	this.get_ResizeRatio = function(){
		return _resizeRatio;
	}
	this.set_ResizeRatio = function(value){
		_resizeRatio = value;
	}
	this.get_PreviousMouseEvent = function(){
		return _previousMouseEvent;
	}
	this.set_PreviousMouseEvent = function(value){
		_previousMouseEvent = value;
	}
	this.get_NextMouseEvent = function(){
		return _nextMouseEvent;
	}
	this.set_NextMouseEvent = function(value){
		_nextMouseEvent = value;
	}
	this.set_CreationDates = function (value) {
		for(var i=0; i<_creationDate.length; i++)
			if (_creationDate[i].getTicks() == value[0].getTicks())
				return;
		_creationDate = value;
	}
	this.get_SelectedTab = function(){
		return _selectedTab;
	}
	this.set_SelectedTab = function(value){
		_selectedTab = value;
	}
	this.set_PlayerURL = function(value){
	    $get('MediaPlayer').URL = value;
	}
	
	//--------methods--------
	
	this.Reset = function(){
		Array.clear(_audioAndMouseCoordinates);
		_previousMouseEvent = 0;
	    _nextMouseEvent = 0;
	    //_selectedTab = 0;
	    if($get('annotationsTabs') !=null)
			$get("annotationTopBar").removeChild($get('annotationsTabs'));
	}
	this.SetUpTabs = function () {

		var table = document.createElement("table");
		table.setAttribute("id", "annotationsTabs");
		var body = document.createElement("tbody");
		var tr = document.createElement("tr");
		for (var i = 0; i < _creationDate.length; i++) {

			var td = document.createElement("td");
			td.setAttribute("id", i);
			$addHandler(td, "click", function () {SelectedAnnotation(this) });
			$addHandler(td, "mouseover", function () { this.style.cursor = 'hand'; this.style.textDecoration = 'underline'; this.style.color = '#ff6600'; });
			$addHandler(td, "mouseout", function () { this.style.textDecoration = 'none'; this.style.color = 'black'; });

			var innerTable = document.createElement("table");
			var innerBody = document.createElement("tbody");
			var innerTr = document.createElement("tr");

			var td1 = document.createElement("td");
			var leftImg = document.createElement("img");
			leftImg.setAttribute('src', 'Images/leftTab.gif');
			td1.appendChild(leftImg);
			innerTr.appendChild(td1);

			var td2 = document.createElement("td");
			td2.style.backgroundImage = "url(Images/tabBackground.gif)";
			td2.style.backgroundRepeat = "repeat-x";
			td2.style.fontSize = 'small';



			var label = document.createTextNode(_creationDate[i].format("d") + " " + _creationDate[i].format("t"));

			td2.appendChild(label);
			innerTr.appendChild(td2);
			var td3 = document.createElement("td");
			var rightImg = document.createElement("img");
			rightImg.setAttribute('src', 'Images/rightTab.gif');
			td3.appendChild(rightImg);
			innerTr.appendChild(td3);

			innerBody.appendChild(innerTr);
			innerTable.appendChild(innerBody);
			innerTable.cellSpacing = 0;
			innerTable.cellPadding = 0;

			td.appendChild(innerTable);
			tr.appendChild(td);
		}
		body.appendChild(tr);
		table.appendChild(body);
		$get("annotationTopBar").appendChild(table);

		//set up css for the table
		table.style.position = 'absolute';
		table.style.zIndex = '9999';
		table.cellSpacing = 0;
		table.cellPadding = 0;
		table.style.float = 'right';
		table.style.right = '32px';


	}
	this.SetUpAnnotationDiv = function(img, isComparison){
		//temporarly turn on resize
		clearInterval(resizeTimerID);
	
		this.set_IsComparison(isComparison);
		this.set_ImageNumber(img);
		
		//set size of div and display the div and close btn
		this.set_Height(g_resizer.clientHeight *.85);
		this.set_Width(g_resizer.clientWidth *.85);
		
		this.CalibrateImageSize();
		
		//modal viewer
		var behavior = $find('AnnotationModalPopUp');
		behavior.show();
		
		$get(this.get_ID()).style.display=""
		$get('dummyCloseBtn').style.display="";
		
		//load image
		this.LoadImage();

		resizeTimerID = setInterval(resize, 100);
	}

	this.LoadImage = function () {
		var comparison = this.get_IsComparison() ? "Series2" : "Series1";
		var imageObj = this.get_IsComparison() ? imageComp : image;
		var img = document.createElement('img');
		img.setAttribute('id', 'annotationImg');
		//img.setAttribute('onerror', imgErrorHandler);
		img.setAttribute('onload', AnnotationImageComplete);
		img.setAttribute('runat', "server");

		$get(this.get_ID()).appendChild(img);

		img = getObject(img.id, Sys.Preview.UI.Image);
		img.set_imageURL("JpegGenerator.ashx?seriesNum=" + imageObj.get_SeriesNum() + '&imgNum=' + this.get_ImageNumber() + "&Width=" + this.get_Width() + "&Height=" + this.get_Height() + "&SplitScreenSeries=" + comparison + "&Window=" + imageObj.get_Window() + "&Level=" + imageObj.get_Level() + "&TimeStamp=" + new Date().getMilliseconds());

		this.DetectBrowser();
		$get('MediaPlayer').style.Top = parseInt(this.get_Height()+20) + 'px';
		$get('MediaPlayer').className = "Video";

		img.get_element().style.position = 'absolute';
		img.get_element().style.top = 22 + 'px';
		img.get_element().style.left = 0 + 'px';

	}
	
	this.CalibrateImageSize = function(){
		if(this.get_Height() > this.get_Width())
			this.set_Height(this.get_Width());
		else
			this.set_Width(this.get_Height());
	}

	this.Close = function () {
		$get('MediaPlayer').controls.stop();
		//turn back on the resize timer
		resizeTimerID = setInterval(resize, 100);
		this.Reset();
		$get(this.get_ID()).removeChild($get('annotationImg'));
		$get(this.get_ID()).removeChild($get('MediaPlayer'));
		if (mouseMovementsInterval != null) {
			clearInterval(mouseMovementsInterval);
		}
	}
	
	this.DetectBrowser = function(){
      if(-1 != navigator.userAgent.indexOf("MSIE"))
      {

		var player = document.createElement('object');
		player.setAttribute('id', "MediaPlayer");
		player.setAttribute('classid', "clsid:6BF52A52-394A-11D3-B153-00C04F79FAA6");
		player.setAttribute('type', "application/x-oleobject");
		$get(this.get_ID()).appendChild(player);
		$get('MediaPlayer').uiMode = 'mini';
		$get('MediaPlayer').enableContextMenu=false;
		
		
      }
      
      else if(-1 != navigator.userAgent.indexOf("Firefox"))
      {
        $get('AnnotationDiv').innerHTML +="<embed  type='application/x-mplayer2'> </embed>";
      }         
	}
	
	
        
        this.GetCurrentMouseCoordinates = function(currentPosition){
            try{ 
                var arrayPosition = findNextMousePosition(_audioAndMouseCoordinates, currentPosition, _previousMouseEvent);
                _nextMouseEvent = _audioAndMouseCoordinates[arrayPosition].audioPosition/1000;
                
                if(arrayPosition != null && (currentPosition>= _audioAndMouseCoordinates[_previousMouseEvent].audioPosition/1000) && (currentPosition <= _nextMouseEvent)){
                    this.UpdatePointerPosition(Math.round(_audioAndMouseCoordinates[arrayPosition].mousePosition.X) , Math.round(_audioAndMouseCoordinates[arrayPosition].mousePosition.Y)+ 21  );
                    _previousMouseEvent = arrayPosition;
                    return;
                }
                else{
                      if(arrayPosition != null && (currentPosition> _audioAndMouseCoordinates[_previousMouseEvent].audioPosition/1000) && (currentPosition > _nextMouseEvent)){
                          _previousMouseEvent = arrayPosition;
                      }
                    }
                }
                catch(ex){
                    return;
                }
        }
        
        
        this.UpdatePointerPosition = function(x,y){
			Sys.UI.DomElement.setLocation($get(this.get_PointerID()),x,y);
        }
	
}
    
    
    function findNextMousePosition(array, target, previousIndex){
        //TODO there seems to be a case where the while loop excutes forever - find it
       var low = previousIndex;
       var high = array.length -1;
        
        while(low <= high){
            var middle = Math.round((high  - low)/2) + low;
             if(low == high || high == low + 1) {
                 trace("return: " + middle);
                 return middle;
            }
            
            else if(target < array[middle].audioPosition/1000){
                high = middle;
            }
       
            else if(target > array[middle].audioPosition/1000){
                low = middle;
            }
            
            else
                return null;
        }
    }




    function SelectedAnnotation(tabClicked) {
		tabClicked.style.color = '#366ab3';
		
		if(mouseMovementsInterval != null){
		    clearInterval(mouseMovementsInterval);
		}
			
		annotation.set_SelectedTab(tabClicked.id);
		var imageObj = annotation.get_IsComparison()? imageComp : image;
		RetrieveVoiceAnnotation(annotation.get_SelectedTab(), annotation.get_IsComparison(), imageObj.get_SeriesNum(), annotation.get_ImageNumber(), annotation.get_Width(), annotation.get_Height());
	}
	function AnnotationImageComplete() {
		
		var imageObj = annotation.get_IsComparison()? imageComp : image;
		//now that the annotation img has loaded resize the annotation div to fit it exactly
		annotation.set_Height($get('annotationImg').clientHeight);
		annotation.set_Width($get('annotationImg').clientWidth);
		$get(annotation.get_ID()).style.height = annotation.get_Height() + 52+'px';
		$get(annotation.get_ID()).style.width  = annotation.get_Width();
		$get('MediaPlayer').style.width = $get('annotationImg').clientWidth + 'px';
		RetrieveVoiceAnnotation(-1, annotation.get_IsComparison(), imageObj.get_SeriesNum(), annotation.get_ImageNumber(), annotation.get_Width(), annotation.get_Height());
		
	}
	
	function CheckDuration(){ 
	  if($get('MediaPlayer') == null || $get('MediaPlayer').controls == null)
              return;
              
            var player = $get('MediaPlayer'); 
            
            //Player states: 
                //0 Undefined Windows Media Player is in an undefined state. 
                //1 Stopped Playback of the current media item is stopped. 
                //2 Paused Playback of the current media item is paused. When a media item is paused, resuming playback begins from the same location. 
                //3 Playing The current media item is playing. 
                //4 ScanForward The current media item is fast forwarding. 
                //5 ScanReverse The current media item is fast rewinding. 
                //6 Buffering The current media item is getting additional data from the server. 
                //7 Waiting Connection is established, but the server is not sending data. Waiting for session to begin. 
                //8 MediaEnded Media item has completed playback.  
                //9 Transitioning Preparing new media item. 
                //10 Ready Ready to begin playing. 
                //11 Reconnecting Reconnecting to stream. 
             
            if(player.playState == 3 || player.playState == 4 || player.playState == 5){
					$get(annotation.get_PointerID()).style.display ='';               
					annotation.GetCurrentMouseCoordinates((player.controls.currentPosition).toFixed(4));
					}
			if(player.playState == 0 || player.playState ==1){
				annotation.set_PreviousMouseEvent(0);
				annotation.set_NextMouseEvent(0);
				$get(annotation.get_PointerID()).style.display ='none';
			 }
				
        }

/*****************************************************
* StudyBrowser Class Object
******************************************************/
function StudyBrowser(value){
	var _isComparison = value;
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	//--------methods--------
	this.Hide = function(){
		var sb = getObject('pnlStudyBrowser', Sys.UI.Control); //new Sys.UI.Control($get('pnlStudyBrowser'));
		sb.set_visible(false);
	}
	
	this.Show = function(){
	 
		$get('hdSeries').value = this.get_IsComparison() ? 'Series2' : 'Series1';
		
		MarkActiveWindow();
		
		var sb = getObject('pnlStudyBrowser', Sys.UI.Control);
		sb.set_visible(true);
		
		
		if($get('ImgPrimDiv') != null)
			$get('btnClose').style.visibility = 'visible';
		else
			$get('btnClose').style.visibility = 'hidden';
		
		//remove any handlers that might still exist from the btnClose
		try{
		$removeHandler($get('btnClose'),'click', Close);
		}
		catch(ex){}
		if($get('ImgCompDiv0') == null && $get('hdSplitScreen').value =='true')
			$addHandler($get('btnClose'),'click', Close);
			
		var behavior = $find('ModalPopUp');
		behavior.show();
	}
	
	
	
	//IconShow
	this.IconEnable = function(){
		var icon = getObject('toolMenuStudyBrowser',Sys.UI.Control);
		icon.get_element().disabled = false;
	}
	
	//IconHide
	this.IconDisable = function(){
		var icon = getObject('toolMenuStudyBrowser',Sys.UI.Control);
		icon.get_element().disabled = true;
	}
}


/*****************************************************
* LayOut Class Object
******************************************************/
function LayOut(value){
	//--------internal variables--------
	var _imgLayOut =0;
	var _isComparison = value;
	var _rows;
	var _cols;
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_ImgLayOut = function(){
		return _imgLayOut;
	}
	this.set_ImgLayOut = function(value){
		_imgLayOut = value;
	}
	this.get_Cols = function(){
		return _cols;
	}
	this.set_Cols = function(value){
		_cols = value;
	}
	this.get_Rows = function(){
		return _rows;
	}
	this.set_Rows = function(value){
		_rows = value;
	}
	

	//--------methods---------
	
	//IconShow
	this.IconEnable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuLayOutComp' : 'toolMenuLayOut';
		var icon =  getObject(obj,Sys.UI.Control);
		icon.get_element().disabled = false;
	}
	
	//IconHide
	this.IconDisable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuLayOutComp' : 'toolMenuLayOut';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().disabled = true;
	}
	
	//LayOut
	this.LayOut = function(e){
		var obj = this.get_IsComparison() ? 'Comp' : '';
			//ToggleLayer('layoutDiv' + obj, event, 'left');
			//$object('layoutDiv' + obj)._popupBehavior.hide();

		switch(this.ImgLayOut){
			case 1: {this._Show('1X1', true); break;}
			case 2: {this._Show('1X2', true); break;}
			case 4: {this._Show('2X2', true); break;}
			default: {
						this._Show('1X1', false); 
						this._Show('1X2', false);
						this._Show('2X2', false); break;
					}
			}
	}
	
	//ChangeLayout
	this.ChangeLayOut = function(rows, cols ){
		var obj = this.get_IsComparison() ? 'Comp' : '';
		$object('layOutExtender' + obj).hidePopup();
		if(this.get_IsComparison())
			imageCacheComp.RemoveCacheImgs(); 
		else
			imageCache.RemoveCacheImgs();
			
		this.set_Rows(rows);
		this.set_Cols(cols);		
		
//		var z1 = getObject('1X1'+obj, Sys.Preview.UI.Image);
//		var z2 = getObject('1X2'+obj, Sys.Preview.UI.Image);
//		var z3 = getObject('1X3'+obj, Sys.Preview.UI.Image);
//		var z4 = getObject('2X1'+obj, Sys.Preview.UI.Image);
//		var z5 = getObject('2X2'+obj, Sys.Preview.UI.Image);
//		var z6 = getObject('2X3'+obj, Sys.Preview.UI.Image);
//		
//		
//		switch(rows*cols){
//			case 1: {	z1.set_visible(true);
//						z2.set_visible(false);
//						z3.set_visible(false);
//						z4.set_visible(false);
//						z5.set_visible(false);
//						z6.set_visible(false);
//					 break;}
//			case 2: {	z1.set_visible(false);
//						z2.set_visible(true);
//						z3.set_visible(false);
//						z4.set_visible(false);
//						z5.set_visible(false);
//						z6.set_visible(false);
//					 break;}
//			case 3: {	z1.set_visible(false);
//						z2.set_visible(false);
//						z3.set_visible(true);
//						z4.set_visible(false);
//						z5.set_visible(false);
//						z6.set_visible(false);
//					 break;}
//				
//			case 4: {	z1.set_visible(false);
//						z2.set_visible(false);
//						z3.set_visible(false);
//						z4.set_visible(true);
//						z5.set_visible(false);
//						z6.set_visible(false);
//					 break;}
//				
//			case 5: {	z1.set_visible(false);
//						z2.set_visible(false);
//						z3.set_visible(false);
//						z4.set_visible(false);
//						z5.set_visible(true);
//						z6.set_visible(false);
//					 break;}
//				
//			case 6: {	z1.set_visible(false);
//						z2.set_visible(false);
//						z3.set_visible(false);
//						z4.set_visible(false);
//						z5.set_visible(false);
//						z6.set_visible(true);
//					 break;}
//				}

	
		var imageObj = this.get_IsComparison() ? imageComp : image;
		//imageObj.SetUpImg(); 
		var viewerSize = this.get_IsComparison() ? $get('ImgCompDiv') : $get('ImgPrimDiv');
		imageObj.set_ImgHeight(viewerSize.clientHeight / this.get_Rows());
		imageObj.set_ImgWidth((viewerSize.clientWidth - (10*this.get_Cols())) /this.get_Cols());
		
	
		//get the scaling information
		//GetSeriesImageNum(imageObj.get_SeriesNum(), imageObj.get_ImgHeight(), imageObj.get_ImgWidth());
		
		var pagingObj = this.get_IsComparison() ? imagePagingComp : imagePaging;
		pagingObj.set_ImagePlaceHoldersCount(this.get_Cols() * this.get_Rows());
		imageObj.SynchronizeThumbs();
		
	}
}

/*****************************************************
* Measure Class Object
******************************************************/
function Measure(value){
	//--------internal variables--------
	var _on = false;
	var _off = false;
	var _isComparison = value;
	var _position0Image = null;
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_On = function(){
		return _on;
	}
	this.set_On = function(value){
		_on = value;
	}
	this.get_Off = function(){
		return _off;
	}
	this.set_Off = function(value){
		_off = value;
	}
	
	//--------methods--------
	
	//MeasureMode
	this.MeasureMode = function(){
		if(!this.MeasureOn())
			this.MeasureModeON();
		else
			this.MeasureModeOFF();
	}
	
	//IconShow
	this.IconEnable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuMeasureComp' : 'toolMenuMeasure';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().disabled = false;
		
	}
	
	//IconHide
	this.IconDisable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuMeasureComp' : 'toolMenuMeasure';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().disabled = true;
	}
	
	//--------internal methods--------
	
	//MeasureOn
	this.MeasureOn = function(){
		var measureIsOn = this.get_On() ? true : false;
		return measureIsOn;
	}
	
	//MeasureModeON
	this.MeasureModeON = function(){
		
		GetImageScaleData(this.get_IsComparison());
		
		//set the image # in position 0 so that we can set it back after when measuring is turned off
		var imageObj = this.get_IsComparison()? imageComp : image;
		_position0Image = imageObj.get_ImgNum();
		
		
		//check to make sure no other tools is on before enabling this tool 
		var tool = this.get_IsComparison() ? zoomComp : zoom;	//zoom is really the only other tool that toggles beside measure
		if(tool.ZoomOn())
			tool.ZoomMode(999);
		
		this.set_On(true);
		DisableToolMenu(this);
		this.AddOverLayDiv();
		
		resizeWindow = false;
	}
	
	//MeasureModeOFF
	this.MeasureModeOFF = function(){
	
	//restore the image # in position 0 
	var imageObj = this.get_IsComparison()? imageComp : image;
	 imageObj.set_ImgNum(_position0Image);
	
	//turn off the icon if it is still on
	var activeIcon;
	if(this.get_IsComparison())
		activeIcon = 'toolMenuMeasureIconComp';
	else
		activeIcon = 'toolMenuMeasureIcon';
	if(this.MeasureOn && $get(activeIcon).style.border != "")
		$get(activeIcon).style.border = "";
		
		this.set_On(false);
		EnableToolMenu(this);
		this.RemoveOverLayDiv();
		
		//make sure that splitScreen is still not active because the browser will 
		if($get('hdSplitScreen').value !='true')
			resizeWindow = true;
	}
	
	//OverLayDiv
	this.AddOverLayDiv = function(){
		var focusDiv =  this.get_IsComparison() ? 'ImgCompDiv' : 'ImgPrimDiv';
		var focuslayOut = this.get_IsComparison() ? layOutComp : layOut;
		var focusImg = this.get_IsComparison() ? imageComp : image;
		var position = 0;
		
		//loop through the rows and cols and add then add a div for drawing on the images
		for(var i = 0; i <focuslayOut.get_Rows(); i++){	
			for(var j = 0; j <focuslayOut.get_Cols(); j++){
				//get the innerHTML
				if($get(focusDiv+position).innerHTML != null)
				focusImg.ImageInnerHTML($get(focusDiv+position).innerHTML);
					//CreateDiv(focusDiv+position, focusDiv+position+ 'Measure','div','MeasureDiv');
					//$get(focusDiv+position+ 'Measure').style.position = 'absolute';
					$get(focusDiv+position+ 'Measure').style.className = 'MeasureOverlay';
					position++;
				}
		}
	}
	
	//RemoveOverLayDiv
	this.RemoveOverLayDiv = function(){
		var focusDiv =  this.get_IsComparison() ? 'ImgCompDiv' : 'ImgPrimDiv';
		var focuslayOut = this.get_IsComparison() ? layOutComp : layOut;
		var focusImg = this.get_IsComparison() ? imageComp : image;
		var position = 0;
		
		//loop through the rows and cols and add then add a div for drawing on the images
		for(var i = 0; i <focuslayOut.get_Rows(); i++){	
			for(var j = 0; j <focuslayOut.get_Cols(); j++){
				//restore the innerHTML
				if($get(focusDiv+position) != null)
					$get(focusDiv+position).innerHTML = focusImg.get_ImageInnerHTML()[position];
					position++;
				}
		}
		
		//clear array that held the innerhtml
		focusImg.RemoveImageInnerHTML();
	}
	
	this.DrawLine = function(e){
		var align;
		var leftShift = leftMargin;		//this is because the entire 'ImgPrimDiv' is shifted to the left by 55px to make room for the thumbs
		var topShift = 40;		//this is because the entire 'ImgPrimDiv' is shifted down by 35px to make room for the menu
		try{
			var location = Sys.UI.DomElement.getLocation($get(e.target.id));
			var SeriesDivLoc = Sys.UI.DomElement.getLocation($get('Series2Div'));
			leftShift = this.get_IsComparison() ? SeriesDivLoc.x + divPadding : leftShift;	//the 5 is because of table margins
			
			var imgEvent = getObject(e.target.id, Sys.UI.Control);
			if(imgEvent.get_element().parentNode.id != null)
			{	
				//get the div on which to dras
				var drawDiv = $get(imgEvent.get_element().parentNode.id +'Measure');
				//used to determine the start mouse loa
				var mouseObj = e.target.id.indexOf('Prim') != -1 ? mouse : mouseComp;
	
                
				var x0 = mouseObj.get_StartLocationX() - leftShift;
				var y0 = mouseObj.get_StartLocationY()-topShift;
				var x1= e.clientX - leftShift;
				var y1= e.clientY -topShift;
				
				drawDiv.innerHTML = this.GenerateInnerHtml(x0, x1, y0, y1, e);
				
			}
		}
		catch(ex){}	//this is because every so often a null reference comes from the event
	}
	
	//GenerateInnerHtml
	this.GenerateInnerHtml = function(x0, x1, y0, y1, e){
	   
		var divText = "";

        var imageObj = e.target.id.indexOf('Prim') != -1 ? image : imageComp;
		var dx = Math.abs((x1-x0)) * imageObj.get_PixelSpacingX(); 
		var dy = Math.abs((y1-y0)) * imageObj.get_PixelSpacingY(); 
		var len = null;
		
		if(imageObj.get_ScalingArithmeticOperation() == 'Division' ){
		    dx = dx * image.get_ResizeRatio();
		    dy = dy * image.get_ResizeRatio(); 
		    } 
		else{
		     dx = dx / image.get_ResizeRatio();
		     dy = dy / image.get_ResizeRatio(); 
		    }

        len = Math.sqrt(Math.pow(dx, 2) + Math.pow(dy, 2));
		
		
		var linespacing = 20;
			for(i=0; i <= linespacing; i++){
				if(i==0 || i==20)
					divText += "<div style='position:absolute; width:1px; height:5px; left:" + (parseInt(x0) + parseInt((x1-x0)/linespacing*i))+"px; top:" + (parseInt(y0) + parseInt((y1-y0)/linespacing*i))+"px; background-color:transparent; font-size:2pt;line-height:2pt; visibility:visible'><img src='Images/menu_item_selected.gif' /></div>\n";
				else
					divText += "<div style='position:absolute; width:3px; height:3px; left:" + (parseInt(x0) + parseInt((x1-x0)/linespacing*i))+"px; top:" + (parseInt(y0) + parseInt((y1-y0)/linespacing*i)+2)+"px; background-color:#366ab3; BORDER-TOP: #366ab3 0px solid;  font-size:2pt;line-height:2pt; visibility:visible'></div>\n";
				
			}
		
		var str = Math.round(len*10)/10.0+"&nbsp;mm";
		
		if(imageObj.get_PixelSpacingX() === 0){
	        str = "?" 
	    }
		return divText+="<div style='position:absolute; zIndex:9900 width:75px; left:"+ (parseInt(x0) + parseInt((x1-x0)+10))+"px; top:" + (parseInt(y0) + parseInt((y1-y0)-15))+"px;clip:rect(0px 75px 15px 0px); BORDER-RIGHT: #efefff 1px solid; BORDER-TOP: #efefff 1px solid; DISPLAY: inline; FONT-WEIGHT: bold;FONT-SIZE: 7pt;Z-INDEX: 9999;VERTICAL-ALIGN: baseline; BORDER-LEFT: #efefff 1px solid; WIDTH: 70px; COLOR: #ffffcc; BORDER-BOTTOM: #efefff 1px solid; FONT-STYLE: normal; FONT-FAMILY: verdana; bottom:17px; BACKGROUND-COLOR: #366ab3; TEXT-ALIGN:center; visibility:visible'>\n"+str+"</div>\n";
		
		
	}
}
function PlotPixel(x, y, c, parentObj) {
	

    var pixel = document.createElement('div');

    pixel.className = 'Ink';

    pixel.style.borderTopColor = c;

    pixel.style.left = x + 'px';

    pixel.style.top = y + 'px';

    parentObj.appendChild(pixel);

}






function DrawLine(x1, y1, x2, y2, c, parentObj) {

    var steep = Math.abs(y2 - y1) > Math.abs(x2 - x1);

    if (steep) {

        t = y1;

        y1 = x1;

        x1 = t;

        t = y2;

        y2 = x2;

        x2 = t;

    }

    var deltaX = Math.abs(x2 - x1);

    var deltaY = Math.abs(y2 - y1);

    var error = 0;

    var deltaErr = deltaY;

    var xStep;

    var yStep;

    var x = x1;

    var y = y1;

    if (x1 < x2) {

        xStep = 1;

    }

    else {

        xStep = -1;

    }

 

    if(y1 < y2) {

        yStep = 1;

    }

    else {

        yStep = -1;

    }

    if(steep) {

        PlotPixel(y, x, c, parentObj);

    }

    else {

        PlotPixel(x, y, c, parentObj);

    }

    while(x != x2) {

        x = x + xStep;

        error = error + deltaErr;

        if(2 * error >= deltaX) {

            y = y + yStep;

            error = error - deltaX;

        }

        if(steep) {

            PlotPixel(y, x, c, parentObj);

        }

        else {

            PlotPixel(x, y, c, parentObj);

        }

    }

}

/*****************************************************
* Zoom Class Object
******************************************************/
function Zoom(value){
	//--------internal variables--------
	var _on = false;
	var _off = false;
	var _factor =1;
	var _isComparison = value;
	
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_On = function(){
		return _on;
	}
	this.set_On = function(value){
		_on = value;
	}
	this.get_Off = function(){
		return _off;
	}
	this.set_Off = function(value){
		_off = value;
	}
	this.get_Factor = function(){
		return _factor;
	}
	this.set_Factor = function(value){
		_factor = value;
	}
	
	
	
	//--------methods--------
	//IconShow
	this.IconEnable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuZoomComp' : 'toolMenuZoom';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().disabled = false;
	}
	
	//IconHide
	this.IconDisable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuZoomComp' : 'toolMenuZoom';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().disabled = true;
	}
	
	//ZoomOn
	this.ZoomOn = function(){
		var zoomIsOn = this.get_On() ? true : false;
		return zoomIsOn;
	}
	this.ToggleZoom = function(value){
		if(!this.ZoomOn())
			this.ZoomMode(value);
		else
			this.ZoomMode(999);
			}
	//ZoomMode
	this.ZoomMode = function(factor){
		this.set_Factor(factor);
		var focusDiv = getObject(this.get_IsComparison() ? 'ImgCompDiv' : 'ImgPrimDiv', Sys.UI.Control);
		var imageObj = this.get_IsComparison() ? imageComp : image;
		var _action = this.get_IsComparison() ? 'Series2' : 'Series1';
		
		var imgCount = focusDiv._element.childNodes.length;
		var obj = this.get_IsComparison() ? 'Comp' :'';
		switch(factor){
			case 1.5: 
			case 2: 
			case 3:
				{	resizeWindow = false;
					this.set_On(true);
					imageObj.CalibrateImageSize();
		
					//zoom.RemoveMagnificationElement(focusDiv);
					var focuslayOut = this.get_IsComparison() ? layOutComp : layOut;
					var position = 0;
					
					//loop through the rows and cols and add then add a magnification image to the magnificationDiv
					for(var i = 0; i <focuslayOut.get_Rows(); i++){	
						for(var j = 0; j <focuslayOut.get_Cols(); j++){
								//determine the size of the image and set it so that the magnification image will double in
								for(var k=0; k<$get(focusDiv.get_id()+ position).childNodes.length; k++){
									if($get(focusDiv.get_id()+ position).childNodes[k].tagName == 'IMG'){
										imageObj.set_ImgWidth($get(focusDiv.get_id()+ position).childNodes[k].offsetWidth);
										imageObj.set_ImgHeight($get(focusDiv.get_id()+ position).childNodes[k].offsetHeight);
										$get(focusDiv.get_id()+ position).childNodes[k].style.filter ="alpha(opacity=85);";//-moz-opacity:.50;opacity:.50';

										}
									}
									
									//I need to check to make sure that the default layout does not have more positions on the page than the series has images
								if(position < imageObj.get_SeriesImgs().length){
									var newImg = CreateDiv(focusDiv.get_id()+ position ,focusDiv.get_id() + position +'MagnificationImage', 'img', 'MagnificationImage');
									
									newImg.style.backgroundImage = "url(Images/LoadingPic.gif)";
									
									//get the magnification image
									var magImg = getObject(newImg.id, Sys.Preview.UI.Image);
									magImg.set_imageURL("JpegGenerator.ashx?seriesNum=" + imageObj.get_SeriesNum() +'&imgNum=' + imageObj.get_SeriesImgs()[imageObj.get_ImgArrayLocation() + position] + "&Width=" + (parseInt(imageObj.get_ImgWidth(),10) * parseFloat(this.get_Factor())) + "&Height=" + (parseInt(imageObj.get_ImgHeight(),10) * parseFloat(this.get_Factor())) +"&SplitScreenSeries=" + _action + "&Window=" + imageObj.get_Window() + "&Level=" + imageObj.get_Level() +"&Magnification=" + true);
									$addHandler($get(focusDiv.get_id() + position), "mouseover",this.MouseTrackAdd);
								
									$addHandler($get(focusDiv.get_id() + position +'MagnificationImage'), "mouseout", this.MouseTrackRemove);
								}
							position++;
							}
						}
					
					//$addHandler(document, 'mousemove', this.MouseTrack);
					
					try{
						$removeHandler(document, "mousedown", MouseDown);
						}
					catch(ex){}	/* I do not want to do anything here, I am simply making sure that the handler is removed if it exists.
									 * I am not aware currently of a better why to do this other than a try-catch*/
					break;
				}
			case 999: //this is the value to turn off the div
			{	//reset the previousImg value back to null
				previousImg = null;
				//turn off the icon if it is still on
				
				var activeIcon;
				if(this.get_IsComparison())
					activeIcon = 'toolMenuZoomIconComp';
				else
					activeIcon = 'toolMenuZoomIcon';
				if(this.ZoomOn() && $get(activeIcon).style.border != "")
					$get(activeIcon).style.border = "";
					
				//set the on property to off
				this.set_On(false);
				
				//$removeHandler(document, 'mousemove', this.MouseTrack);
				$addHandler(document, "mousedown", MouseDown);
				this.RemoveMagnificationElement(focusDiv);
				imageObj.set_ShowLabels(false);
				imageObj.ToggleLabel();
				//resizeWindow = true;
				break;
			}
			
			
		}
	}
	
	//ChangeZoom
	this.ChangeZoom = function(value){
		
		this.ZoomMode(value);
	}
	
	//--------internal methods--------
	

	/*************************************************************************************************/
// remove the magnification image from the div
/*************************************************************************************************/
	this.RemoveMagnificationElement = function(parentNode) {

	   var len = parentNode._element.childNodes.length;
		  for(var i=len; i>0; i--)
		  {
			try{
				parentNode._element.childNodes[i-1].removeChild( parentNode._element.childNodes[i-1].childNodes[1]);
				}
			catch(ex)
			{/*if gets in here that means it doesn't exist to remove so no big deal*/}
		  }

	}
	
/*************************************************************************************************/
// MouseTrackRemove - remove the mouse track event
/*************************************************************************************************/
this.MouseTrackRemove = function(e){
		$removeHandler(document, "mousemove",MouseTrack);
		$get(e.target.id).style.clip="rect(0px,0px,0px,0px)";
}
this.MouseTrackAdd = function(e){
		$addHandler(document, "mouseover",MouseTrack);
		
		// since javascript does not support a means by detecting if an event handler is already registered
		// so I just always try to remove the handler and if it is not registered then it will throw and error
		// of which I will catch and do nothing.
		try{
			$removeHandler(document, "mousemove",MouseTrack);
		}
		catch(ex){}
		
		$addHandler(document, "mousemove",MouseTrack);
		//remove this handler now, since we only want it on the inital tracking.
		$removeHandler(document, "mouseover",MouseTrack);
}
}
/*************************************************************************************************/
// MouseTrack - track the mousemove event while in zoom mode
/*************************************************************************************************/
	//this.MouseTrack = function(e){
	function MouseTrack(e){
		var xShift = -leftMargin;
		var yShift = -topMargin;
		var x,y,x1,x2,y1,y2,dx=0,dy=0;
		var opp=50;
		var activeImg;	
		var img;
		var series2 = false;
		if(e.target.id.startsWith('MagnificationImage') || (e.target.id.startsWith('Img') && e.target.id.indexOf('Label') == -1)){
				
				var activeDiv = (e.target.id != 'ImgPrimDiv' && e.target.id != 'ImgCompDiv') ? getObject(e.target.id,Sys.UI.Control).get_parent() : getObject(e.target.id,Sys.UI.Control);//getObject('ImgPrimDiv',Sys.UI.Control);
				try{
			
					if(activeDiv._element.id.indexOf('Comp') != -1)
							series2 = true;
							
					img = getObject(e.target.parentNode.id+'MagnificationImage', Sys.Preview.UI.Image)

					if(img.get_element().id != previousImg ){
							img.get_element().style.visibility ='visible';
							img.get_element().style.zIndex='9999';
					}
						
					if(series2){
						
						var location = Sys.UI.DomElement.getLocation(e.target.parentNode);
						x = e.clientX -location.x;
						y = e.clientY -location.y;
						dx=img._element.scrollLeft;
						dy=img._element.scrollTop;
						
						x1=-opp+(x+dx)* zoomComp.get_Factor();   

						y1=-opp+(y+dy)* zoomComp.get_Factor();   

						x2=+opp+(x+dx)* zoomComp.get_Factor();  

						y2=+opp+(y+dy)* zoomComp.get_Factor();  
					
						
						img._element.style.clip="rect(" +y1 +"px," +x2 +"px," +y2 +"px,"  +x1 +"px)";
						
						var anchorLoc = Sys.UI.DomElement.getLocation($get("ImgCompDivTable"));
						img._element.style.left=(location.x - x - anchorLoc.x )+"px";
						img._element.style.top=(location.y - y - anchorLoc.y)+"px";
					}
					else{
						var location = Sys.UI.DomElement.getLocation(e.target.parentNode);
						x = e.clientX -location.x;
						y = e.clientY -location.y;
						dx=img._element.scrollLeft;
						dy=img._element.scrollTop;
						
						x1=-opp+(x+dx)* zoom.get_Factor();   

						y1=-opp+(y+dy)* zoom.get_Factor();   

						x2=+opp+(x+dx)* zoom.get_Factor();  

						y2=+opp+(y+dy)* zoom.get_Factor();  
						
						img._element.style.clip="rect(" +y1 +"px," +x2 +"px," +y2 +"px,"  +x1 +"px)";
						
						img._element.style.left=(location.x - x - leftMargin)+"px";
						img._element.style.top=(location.y - y - topMargin)+"px";
					}
					
					
					

					
				}
					catch (ex){/* I will not catch log the exception, only catch it to prevent the browser script error event from bubbling up*/}
					
		}
	}
//}
function Rectangle(x, y, height, width){
	this._x = x;
	this._y = y;
	this._height = height;
	this._width = width;
	
	//--------properties--------
	
}

/*****************************************************
* Class WindowLevel
******************************************************/
function WindowLevel(value){
	//--------internal variables--------
	var _activeImgID = null;
	var _on = false;
	var _isComparison = value;
	var _namePresets = new Array();
	var _windowPresets = new Array();
	var _levelPresets = new Array();
	var _window = 0;
	var _level = 0;
	var _rect = null;
	var _levelLabel = 'levelLabel';
	var _windowLabel = 'windowLabel';
	var _defaultLevel = 0;
	var _defaultWindow = 0;
	
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_On = function(){
		return _on;
	}
	this.set_On = function(value){
		_on = value;
	}
	this.get_Window = function(){
		return _window;
	}
	this.set_Window = function(value){
		_window= value;
	}
	this.get_Level = function(){
		return _level;
	}
	this.set_Level = function(value){
		_level= value;
	}
	this.get_DefaultWindow = function(){
		return _defaultWindow;
	}
	this.set_DefaultWindow = function(value){
		_defaultWindow= value;
	}
	this.get_DefaultLevel = function(){
		return _defaultLevel;
	}
	this.set_DefaultLevel = function(value){
		_defaultLevel= value;
	}
	this.get_LevelLabel = function(){
		return _levelLabel;
	}
	this.set_LevelLabel = function(value){
		_levelLabel= value;
	}
	this.get_WindowLabel = function(){
		return _windowLabel;
	}
	this.set_WindowLabel = function(value){
		_windowLabel= value;
	}
	this.get_ActiveImgID = function(){
		return _activeImgID;
	}
	this.set_ActiveImgID = function(value){
		_activeImgID= value;
	}
	
	
	//--------methods--------
	this.Rectangle = function(x, y, height, width){
		var rect = new Rectangle(x,y, height, width);
		this._rect = rect; 
	}
	
	this.UpdateWindowLevelLabels = function(){
		var imgObj = this.get_IsComparison()? imageComp : image;
		$get(_levelLabel).innerText = String.format("{0}{1}","L: ",imgObj.get_Level())
		$get(_windowLabel).innerText = String.format("{0}{1}","W: ",imgObj.get_Window())
	}
	
	this.WindowLevelCurrentImage = function(e){
	//trace("WindowLevelCurrentImage");
		var imageObj = image;
		var mouseObj = mouse;
		
		if(this.get_IsComparison()){
			imageObj = imageComp;
			mouseObj = mouseComp;
		}
		
		//trace("Window: " + imageObj.get_Window() + " Level: " + imageObj.get_Level());
		
		
		//get the current mouse coordinates
		if(e.clientX - mouseObj.get_StartLocationX() < -2) {
		//trace(String.format("{0}{1}{2}{3}","current: ",e.clientX,"  mouseDownLocaton:", mouseObj.get_StartLocationX()));
			imageObj.set_Window(imageObj.get_Window() - 3 * (parseInt(mouseObj.get_StartLocationX() - e.clientX)));
		
			//set the new start location to where I left off
			mouseObj.set_StartLocationX(e.clientX);
			
			this.GetNewWindowLevelImg();
		}
		if(e.clientY - mouseObj.get_StartLocationY() < -2){
		//trace(String.format("{0}{1}{2}{3}","current: ",e.clientY,"  mouseDownLocaton:", mouseObj.get_StartLocationY()));
			imageObj.set_Level(imageObj.get_Level() - 3 * (parseInt(mouseObj.get_StartLocationY() - e.clientY))); 
		
			//set the new start location to where I left off
			mouseObj.set_StartLocationY(e.clientY);
			
			this.GetNewWindowLevelImg();
		}
		if(e.clientX - mouseObj.get_StartLocationX()>2){
		//trace(String.format("{0}{1}{2}{3}","current: ",e.clientX,"  mouseDownLocaton:", mouseObj.get_StartLocationX()));
			imageObj.set_Window(imageObj.get_Window() + 3 * (parseInt(e.clientX - mouseObj.get_StartLocationX())));
		
			//set the new start location to where I left off
			mouseObj.set_StartLocationX(e.clientX);
			
			this.GetNewWindowLevelImg();
		}
		if(e.clientY - mouseObj.get_StartLocationY()>2){
		//trace(String.format("{0}{1}{2}{3}","current: ",e.clientY,"  mouseDownLocaton:", mouseObj.get_StartLocationY()));
			imageObj.set_Level(imageObj.get_Level() + 3 * (parseInt(e.clientY - mouseObj.get_StartLocationY()))); 
		
			//set the new start location to where I left off
			mouseObj.set_StartLocationY(e.clientY);
			
			this.GetNewWindowLevelImg();
		}
	}
	
	this.GetNewWindowLevelImg = function(){
		imageObj = this.get_IsComparison() ? imageComp : image;
		
		this.UpdateWindowLevelLabels();

		var currentImgURL = $get(this.get_ActiveImgID()).src;
			if(currentImgURL !=null){	
					var img = getObject('WindowLevelingImg',Sys.Preview.UI.Image);
					var url = currentImgURL.replace(currentImgURL.substring(currentImgURL.indexOf("&Window")), "&Window=" + imageObj.get_Window() + "&Level=" + imageObj.get_Level() +"&Rect=" + String.format("{0}{1}{2}{3}{4}{5}{6}", this._rect._x,':',this._rect._y,':',this._rect._width,':',this._rect._height));
					
					if(this.get_IsComparison())
						url = String.format("{0}{1}", url,"&SplitScreenSeries=Series2"); 
						
					img.set_imageURL(url);				
					img._element.style.display ='';
				

				
			}
	}
	
	this.ChangeWindowLevelByPreset = function(i){
		var imageObj = image;
		var extender = 'windowLevelExtender';
		
		reloadWinLevelOptions = false;
		
		if(this.get_IsComparison()){
			imageObj = imageComp;
			extender = 'windowLevelExtenderComp';
			}
		//hide the submenu for window level
		$object(extender).hidePopup();
		
		//use the index to get the corresponding Window, Level values, if the value is 0 set back to the default window/level
		if(this.windowPresets[i] == 0){
			imageObj.set_Window(this.get_DefaultWindow());
			imageObj.set_Level(this.get_DefaultLevel());
			}
		else{
			imageObj.set_Window(this.windowPresets[i]);
			imageObj.set_Level(this.levelPresets[i]);
		}
		
		//loop through and clear previously selected window level presets
		for(var k = 0; k <this.namePresets.length; k++){
			if( k == i)
				$get(this.namePresets[i]+$get('hdSeries').value+'img').src="Images/menu_item_selected.bmp";
			else
				$get(this.namePresets[k]+$get('hdSeries').value+'img').src="Images/menu_item_notselected.bmp";
			}
	
		//ajax method that sets the new window / level values in the series session object
		SetWindowLevel(imageObj.get_Window(), imageObj.get_Level());
		
		//remove the cached images so that the new images will load into the cache with the new window level
		var cache = this.get_IsComparison() ? imageCacheComp : imageCache;
		cache.RemoveCacheImgs();
		//imageObj.LoadImages(); 
		imageObj.SynchronizeThumbs();
		
		
	}
	//IconShow
	this.IconEnable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuWindowLevelComp' : 'toolMenuWindowLevel';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().disabled = false;
	}
	
	//IconHide
	this.IconDisable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuWindowLevelComp' : 'toolMenuWindowLevel';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().disabled = true;
	}
	
	this.WindowLevelDefaults = function(){
		image.set_Window(0);
		image.set_Level(0);
	}
	//--------internal methods--------
}

/*****************************************************
* Comparision Class Object
******************************************************/
function Comparison(){
	//--------internal variables--------
	var _on = false;
	//--------properties--------
	this.get_On = function(){
		return _on;
	}
	this.set_On = function(value){
		_on = value;
	}
	//--------methods--------
	
	//IconShow
	this.IconEnable = function(){
		var icon = getObject('toolMenuComparison',Sys.UI.Control);
		icon.get_element().disabled = false;
	}
	
	//IconHide
	this.IconDisable = function(){
		var icon = getObject('toolMenuComparison',Sys.UI.Control);
		icon.get_element().disabled = true;
	}
	
	this.ComparisonMode = function(){
		if(!this.get_On())
			this.SetUpComparisonDiv();
		else
			this.RemoveComparisonDiv();	
	}
	
	
	//MeasureOn
	
	//SetUpComparisonDiv 
	this.SetUpComparisonDiv = function(){
			
			//set internal switch to on
			this.set_On(true);
			
//			//block the window resize event from firing
//			resizeWindow = false;
			//set the splitScreen mode to true
			$get('hdSplitScreen').value = true;

			//swap out the div cssClass
			var imgPrimDiv = getObject('ImgPrimDiv', Sys.UI.Control);
			imgPrimDiv.removeCssClass('DivViewerFull');
			imgPrimDiv.addCssClass('ImgPrimDiv');
			imgPrimDiv.get_element().style.width = ((g_resizer.clientWidth - (leftMargin * 2) -divPadding)/2)+'px';	//in comparison mode the width is divided in half;
		
			//Redo the images to the new format
			image.SetUpImg();
			comparisonMode = true;
			
			this.AddComparisonElement();
			
			//remove the study thumbnail strip
			if($get('SeriesThumbs')!=null)
			$get('SeriesThumbsDiv').removeChild($get('SeriesThumbs'));
			
	}
	
	//RemoveComparisonDiv
	this.RemoveComparisonDiv = function(){
		
		//set internal switch to on
		this.set_On(false);
		
		//set the splitScreen mode to true
		$get('hdSplitScreen').value = false;
		 //set the session paramater "ActiveSeriesWindow" to hold the "Series2"
	    $get('hdSeries').value = "Series1";
		MarkActiveWindow();
		
		$get('ImgPrimDiv').style.width ="";
		var imgPrimDiv = getObject('ImgPrimDiv', Sys.UI.Control);
		imgPrimDiv.removeCssClass('ImgPrimDiv');
		imgPrimDiv.addCssClass('DivViewerFull'); 
		imgPrimDiv.get_element().style.width = ((g_resizer.clientWidth - (leftMargin * 2)))+'px';
		imgPrimDiv.get_element().style.height = (g_resizer.clientHeight - topMargin) +'px';
		
		comparisonMode = false;
		
		//Remove Comparision Session Object
		CreateComparisionObject(false);
		this.RemoveAllComparisionObjects();
		
		//enable the window resize event 
		resizeWindow = true;
		
		toolbar.LoadToolbars();
        
        //Redo the images to the new format
		GetSeriesImageNum(image.get_SeriesNum());
		
	}
	
	//--------internal methods--------
	//AddComparisonElement 
	this.AddComparisonElement = function(){
		$get('Series2Div').style.display = '';
		CreateDiv('Series2Div', 'ImgCompDiv','div','ImgCompDiv');
		var imgCompDiv = getObject('ImgCompDiv', Sys.UI.Control);
			imgCompDiv.get_element().style.width = ((g_resizer.clientWidth - (leftMargin * 2) - divPadding)/2)+'px';
			imgCompDiv.get_element().style.height = (g_resizer.clientHeight - (2*topMargin)) + 'px';
		
		this.AddComparisonLabeling();
		 //Set Up Comparision Session Object
	    CreateComparisionObject(true);
	    
	    studyBrowser.Show();
	    
	    //set the session paramater "ActiveSeriesWindow" to hold the "Series2"
	    $get('hdSeries').value = "Series2";
		MarkActiveWindow();
	}
	//AddComparisonLabeling
	this.AddComparisonLabeling = function(){
		toolbarComp.LoadToolbars();
        
        //hide 
        $get('StudySeriesThumbStrip').style.display = 'none';
		
	}
	
	//RemoveAllComparisonObjects
	this.RemoveAllComparisionObjects = function(){
		$get('Series2Div').removeChild($get('ImgCompDiv'));
		imageComp.RemoveTabsAndTools();
		$get("toolMenuReportCompIcon").src = "Images/48_Reports.png";
		$get('toolMenuComp').style.display='none';
		$get('CompThumb').style.display='none';
		$get('Series2Div').style.display = 'none';
	}
}

/*****************************************************
* Report Class Object
******************************************************/
function Report(value){
	//--------internal variables--------
	var _url = null;
	var _isComparison = value;
	var _hasReport = false;
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_Url = function(){
		return _url;
	}
	this.set_Url = function(value){
		_url= value;
	}
	this.get_hasReport = function(){
		return _hasReport;
	}
	this.set_hasReport = function(value){
		_hasReport = value;
	}
	//--------methods--------
	
	//IconShow
	this.IconEnable = function(){
		var obj = this.get_IsComparison() ? 'toolMenuReportComp' : 'toolMenuReport';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().style.display = '';
	}
	
	//IconHide
	this.IconDisable = function(tooltip){
		var obj = this.get_IsComparison() ? 'toolMenuReportComp' : 'toolMenuReport';
		var icon = getObject(obj,Sys.UI.Control);
		icon.get_element().onclick ="";
		icon.get_element().title =tooltip;
		icon.get_element().childNodes[0].src = "Images/48_ReportsDisabled.png"
		//toolMenuReportCompIcon
		
	}
	this.LaunchReport = function(reportUrl){
	    var url = (reportUrl === null || typeof(reportUrl) == "undefined" || reportUrl ==='') ?  this.get_Url() : reportUrl;
		GetAuthenticationTicket(url);
	}
	
	//--------internal methods--------
}

/*****************************************************i
* SeriesNavigationToolbar Class Object
******************************************************/
function SeriesNavigationToolbar(comparison){
	//--------internal variables--------
	var _imgNumDisplayFirst;
	var _imgNumDisplayLast;
	var _isComparison = comparison;
	
	//--------properties--------
	
	this.get_ImgNumDisplayFirst = function(){
		return _imgNumDisplayFirst;
	}
	this.set_ImgNumDisplayFirst = function(value){
		 _imgNumDisplayFirst = value;
	}
	this.get_ImgNumDisplayLast = function(){
		return _imgNumDisplayLast;
	}
	this.set_ImgNumDisplayLast = function(value){
		_imgNumDisplayLast = value;
	}
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	//--------methods--------
	
	//IconShow
	this.IconShow = function(){
//		var obj = this.get_IsComparison() ? 'seriesNavigationToolBarComp' : 'seriesNavigationToolBar';
//		var icon = getObject(obj,Sys.UI.Control);
//		icon.set_visible(true);
//		icon._element.style.display ='';
	}
	
	//IconHide
	this.IconHide = function(){
//		var obj = this.get_IsComparison() ? 'seriesNavigationToolBarComp' : 'seriesNavigationToolBar';
//		var icon = getObject(obj,Sys.UI.Control);
//		icon.set_visible(false);
//		icon._element.style.display ='none';
	}
	
	//
	this.LoadToolbar = function(){
		var imageObj = this.get_IsComparison() ? imageComp : image;
		var layOutObj = this.get_IsComparison() ? layOutComp : layOut;
		//var imgNumDisplayFirst = imageObj.get_ImgNum();
		//var imgNumDisplayLast = parseInt(imageObj.get_ImgNum(),10) + parseInt(layOutObj.get_Rows() * layOutObj.get_Cols(),10) -1;
		var divFocus = this.get_IsComparison() ? 'Comp' : 'Prim';
			
		if(this.get_ImgNumDisplayLast() != this.get_ImgNumDisplayFirst())
			$get('lblImgShown' + divFocus).innerText = " Images: " + this.get_ImgNumDisplayFirst() +"-"+ this.get_ImgNumDisplayLast() +" ";
		else
			 $get('lblImgShown' + divFocus).innerText = " Image: " + this.get_ImgNumDisplayFirst()+" ";
	}
	
	this.ScrollImg = function(direction){
		var imageObj = this.get_IsComparison() ? imageComp : image;
		if(direction == "next"){
			if(imageObj.get_ImgArrayLocation() + 1 < imageObj.get_SeriesImgs().length - 1){
				imageObj.set_ImgNum(imageObj.get_SeriesImgs()[imageObj.get_ImgArrayLocation()+1]);
				imageObj.LoadImages();
				}
		  }
		else
		{
			if(imageObj.get_ImgArrayLocation()-1 >= 0){
			 imageObj.set_ImgNum(imageObj.get_SeriesImgs()[imageObj.get_ImgArrayLocation()-1]);
				imageObj.LoadImages();
			}    
		}
	}
	
	//--------internal methods--------
}

function Study(){
	//--------internal members--------
	var _series = new Array();
	var _middleImg = new Array();
	var _seriesDesc = new Array();
	var _patientName;
	var _patientID="";
	var _DOB = "";
	var _accession="";
	var patientGroup="";
	var _window = 0;
	var _level = 0;
	var _studyDesc;
	var _modality;
	var _studyUID = null;
	
	//--------properties--------
	this.get_FullName= function(){
		return _patientName;
	}
	this.set_FullName = function(value){
		_patientName = value;
	}
	this.get_PatientID= function(){
		return _patientID;
	}
	this.set_PatientID = function(value){
		_patientID = value;
	}
	this.get_DOB = function(){
		 d = new Date(_DOB);
		return d.localeFormat("d");
	}
	this.set_DOB = function(value){
		_DOB = value;
	}
	this.get_Accession= function(){
		return _accession;
	}
	this.set_Accession = function(value){
		_accession = value;
	}
	this.get_PatientGroup= function(){
		return _patientGroup;
	}
	this.set_PatientGroup= function(value){
		_patientGroup = value;
	}
	
	this.get_AllStudySeries = function(){
		return _series;
	}
	this.get_AllSeriesMiddleImg = function(){
		return _middleImg;
	}
	this.get_StudyDesc= function(){
		return _studyDesc;
	}
	this.set_StudyDesc = function(value){
		_studyDesc = value;
	}
	this.get_Modality= function(){
		return _modality;
	}
	this.set_Modality = function(value){
		_modality = value;
	}
	this.get_SeriesDesc= function(){
		return _seriesDesc;
	}
	this.set_SeriesDesc = function(value){
		_seriesDesc = value;
	}
	this.get_StudyUID= function(){
		return _studyUID;
	}
	this.set_StudyUID = function(value){
		_studyUID = value;
	}
	//adds a new Study series to the array
	this.AddStudySeries = function(imgNum){
		Array.add(_series, imgNum);
	}
	
	//adds the middle img to the array
	this.AddSeriesMiddleImg = function(imgNum){
		Array.add(_middleImg, imgNum);
	}
	//adds the description of the series to the series array
	this.AddSeriesDesc = function(desc){
		Array.add(_seriesDesc, desc);
	}
	
	//ClearAllStudySeries - remove all elements in the array
	this.ClearAllStudySeries = function(){
		//Array.clear(_allStudySeries);
		Array.clear(_series);
		Array.clear(_middleImg);
		Array.clear(_seriesDesc);
	}
}
/*****************************************************
* Series Class Object - this class is inherited by image (Base Class)
******************************************************/
function Series(value){
	//--------internal variables--------
	var _series = new Array();		//inheritance varialble
	var _middleImg = new Array();	//inheritance varialble
	var _seriesDesc = new Array();	//inheritance varialble
	
	var _compressionRatios = new Array();
	var _seriesImg = new Array();
	var _allStudySeries = new Array();
	var _imageInnerHTML = new Array();
	
	var _isComparison = value;
	var _window = 0;
	var _level = 0;
	var _seriesNum;
	var _currentSeriesDesc;
	var _multiFrame = false;
	var _scrollingDir = 'undefined';
	var _middleImage = null;
	var _thumbNails = 5;
	var _defaultThumbNails = 5;
	var _version = new Array();
	
	//translated strings that I may need
	var _imageCompressed = null;
	
	 
	//--------properties--------
	this.get_StringImageCompressed = function(){
		return _imageCompressed;
	}
	this.set_StringImageCompressed = function(value){
		_imageCompressed = value;
	}
	this.get_CompressionRatios = function(){
		return _compressionRatios;
	}
	this.set_CompressionRatios = function(value){
		//Array.clear(_compressionRatios);
		var emptyArray = new Array();
		_compressionRatios = emptyArray;
		_compressionRatios = value;
	}
	this.get_Version = function(){
		return _version;
	}
	this.set_Version = function(value){
		_version = value;
	}
	this.get_ImageInnerHTML = function(){
		return _imageInnerHTML;
	}
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_Window = function(){
		return _window;
	}
	this.set_Window = function(value){
		_window = value;
	}
	this.get_Level = function(){
		return _level;
	}
	this.set_Level = function(value){
		_level = value;
	}
	this.get_SeriesImgs = function(){
		return _seriesImg;
	}
	this.set_SeriesImgs = function(value){
		_seriesImg = value;
	}
	this.get_SeriesNum = function(){
		return _seriesNum;
	}
	this.set_SeriesNum = function(value){
		_seriesNum = value;
	}
	this.get_CurrentSeriesDesc = function(){
		return _currentSeriesDesc;
	}
	this.set_CurrentSeriesDesc = function(value){
		_currentSeriesDesc = value;
	}
	this.get_MultiFrame = function(){
		return _multiFrame;
	}
	this.set_MultiFrame = function(value){
		_multiFrame = value;
	}
	this.get_ScrollingDir = function(){
		return _scrollingDir;
	}
	this.set_ScrollingDir = function(value){
		_scrollingDir = value;
	}
	this.get_MiddleImage = function(){
		return _middleImage;
	}
	this.set_MiddleImage = function(value){
		_middleImage = value;
	}
	this.get_ThumbNailCount = function(){
		return _thumbNails;
	}
	this.set_ThumbNailCount = function(value){
		_thumbNails = value;
	}
	this.get_DefaultThumbNailCount = function(){
		return _defaultThumbNails;
	}
	
	
	
	//--------methods--------
	
	//AddSeriesImg
	
	this.AddSeriesImg = function(value){
		//Array.add(_seriesImg, imgNum);
		_seriesImg = value;
	}
	
	//ClearSeries - remove all elements in the array
	this.ClearSeries = function(){
		Array.clear(_seriesImg);
	}
	
	this.ClearImags
	
	//ImageInnerHTML
	this.ImageInnerHTML = function(html){
		Array.add(_imageInnerHTML, html);
	}
	
	//RemoveImageInnerHTML
	this.RemoveImageInnerHTML = function(){
		Array.clear(_imageInnerHTML);
	}
	
	//ShowTabsAndTools
	this.ShowTabsAndTools = function(){
//		if(this.get_IsComparison())
//			seriesNavigationToolBarComp.IconShow();
//		else
//			seriesNavigationToolBar.IconShow();
			
	}
	//
	this.RemoveTabsAndTools = function(){
//		if(this.get_IsComparison())
//			seriesNavigationToolBarComp.IconHide();
//		else
//			seriesNavigationToolBar.IconHide();
	}
	
	//--------internal methods--------
}


/*****************************************************
* Image Class Object
******************************************************/
function Image(value){
	//--------internal variables--------
	//in so called Object Oriented Javascript all base class variables are not accessible from the derived class and thus must be redefined, the gets and sets since are function are accessible
	var _seriesImg = new Array();
	var _allStudySeries = new Array();
	var _voiceAnnotation = new Array();
	var _imagesOnScreen = new Array();
	var _isComparison = value;
	var _window = 0;
	var _level = 0;
	var _seriesNum;
	var _multiFrame = false;
	var _middleImage = null;
	
	
	
	var _imgNum = 0;
	var _comparision = false;
	var _resizeRatio = 1;
	var _imgHeight = 0;
	var _imgWidth = 0;
	var _pixelSpacingX = 0;
	var _pixelSpacingY = 0;
	var _showLabels = true; 
	var _position = 0;
	var _activeImg;
	var _currentIndex;
	var _scalingArithmeticOperation;
	var _timeStamp = null;
	var _imagesLoadCount = 0;
	var _dicomImgHeight = 0;
	var _dicomImgWidth = 0;
	var _preLoadedImgNums = new Array();
	
	
	
	//--------properties--------
	this.get_PreLoadedImgNums = function(){
		return _preLoadedImgNums;
	}
	
	this.get_ImgNum = function(){
		return _imgNum;
	}
	this.set_ImgNum = function(value){
		_imgNum = value;
	}
	
	this.get_ResizeRatio = function(){
		return _resizeRatio;
	}
	this.set_ResizeRatio = function(value){
		_resizeRatio = value;
	}
	this.get_ImgHeight = function(){
		return _imgHeight;
	}
	this.set_ImgHeight = function(value){
		_imgHeight = value;
	}
	this.get_ImgWidth = function(){
		return _imgWidth;
	}
	this.set_ImgWidth = function(value){
		_imgWidth = value;
	}
	this.get_DicomImgHeight = function(){
		return _dicomImgHeight;
	}
	this.set_DicomImgHeight = function(value){
		_dicomImgHeight = value;
	}
	this.get_DicomImgWidth = function(){
		return _dicomImgWidth;
	}
	this.set_DicomImgWidth = function(value){
		_dicomImgWidth = value;
	}
	this.get_PixelSpacingX = function(){
		return _pixelSpacingX;
	}
	this.set_PixelSpacingX = function(value){
		_pixelSpacingX = value;
	}
	this.get_PixelSpacingY = function(){
		return _pixelSpacingY;
	}
	this.set_PixelSpacingY = function(value){
		_pixelSpacingY = value;
	}
	this.get_ShowLabels = function(){
		return _showLabels;
	}
	this.set_ShowLabels = function(value){
		_showLabels = value;
	}
	this.get_Position = function (){
		return _position;
	}
	this.set_Position = function(value){
		_position = value;
	}
	this.get_ActiveImg = function(){
		return _activeImg;
	}
	this.set_ActiveImg = function(value){
		_activeImg = value;
	} 
	this.get_CurrentArrayIndex = function(){
		return _currentIndex;
	}
	this.set_CurrentArrayIndex = function(value){
		_currentIndex = value;
	} 
	this.set_VoiceAnnotation = function(value){
		_voiceAnnotation = value;
	} 
	this.get_VoiceAnnotation = function(){
		return _voiceAnnotation;
	} 
	this.get_SeriesArray = function(){
		return this.get_SeriesImgs();
	}
	this.set_ScalingArithmeticOperation = function(value){
		_scalingArithmeticOperation = value;
	} 
	this.get_ScalingArithmeticOperation = function(){
		return _scalingArithmeticOperation;
	} 
	this.get_TimeStamp = function(){
		return _timeStamp;
	}
	this.set_TimeStamp = function(value){
		_timeStamp = value;
	}
	this.get_ImagesOnScreen = function(){
		return _imagesOnScreen;
	}
	this.set_ImagesOnScreen = function(value){
		Array.add(_imagesOnScreen, value);
	}
	this.get_ImagesLoadCount = function(){
		return _imagesLoadCount;
	}
	this.set_ImagesLoadCount = function(value){
		_imagesLoadCount = value;
	}
	
	
	
	this.get_ImgArrayLocation = function(){
		for(var i = 0; i<this.get_SeriesImgs().length; i++)
		{
			if(this.get_SeriesImgs()[i]  == this.get_ImgNum())
				break;
		}
		return i;
	}
	
	
	//--------methods--------
	//returns true when all the main images on the viewer.aspx are loaded, these do not include thumbnails
	this.AllImagesLoaded = function(){
		return _imagesLoadCount == _imagesOnScreen.length ? true : false;
	}
	this.Reset = function(){
		_window = 0;
		_level = 0;
		_seriesNum;
		_multiFrame = false;
		_scrollingDir = 'undefined';

		_imgNum = 0;
		_comparision = false;
		_resizeRatio = 1;
		_imgHeight = 0;
		_imgWidth = 0;
		_pixelSpacingX = 1;
		_pixelSpacingY = 1;
		_showLabels = true; 
		_position = 0;
		_activeImg = null;
		_currentIndex = null;
		Array.clear(_imagesOnScreen);
		Array.clear(_voiceAnnotation);
		
	}
	
	this.HasVoiceAnnotation = function(imgNum){
		for(var i = 0; i<this.get_VoiceAnnotation().length; i++)
		{
			if(this.get_VoiceAnnotation()[i]  == imgNum)
				return "display:;";
		}
		return 'display:none;';
	}
	
	//used to toggle on and off the labels that appear over the images
	this.ToggleLabel = function(){
		var oppositecurrent = this.get_ShowLabels()? false: true;
		this.set_ShowLabels(oppositecurrent);
		this.LoadImages();
	}
	
	//set the label property
	this.ShowLabels = function(){
		return this.get_ShowLabels()? true :false;
	}
	
	//ParesImgNum - break out the series and imgNum
	this.ParseImgNum = function(imageNum){
		if (imageNum.indexOf(':') != -1) {
			var temp = new Array(2);
				temp = imageNum.split(':');
				this.set_SeriesNum(temp[0]);
				_imgNum = temp[1];
				}
		else 
			_imgNum = imageNum;
			
		return this.get_ImgNum();
	}
	
	//gets the window level property for the image/series
	this.GetWindow = function(){
			return this.get_Window();
	}
	
	//gets the window level property for the image/series
	this.GetLevel = function(){
			return this.get_Level();
	}
	
	
	
	
	//ImageResizeRatio
	this.ImageResizeRatio = new function(){
	}
	
	//SetUpImg() - 
	this.SetUpImg = function(imgNum){
	
	var thumbs;
		if(imgNum!=null){
			if(!this.get_IsComparison()){
				image.ParseImgNum(imgNum);
				//windowLevel.WindowLevelDefaults();	//restore WL to thumbs
				if($get('ImgPrimDiv') == null)
					AddPrimaryElement();
					
					//check to see if a report is marked available in the PACS database
					if(report.get_hasReport())
						$get("toolMenuReportIcon").src = "Images/48_ReportAvailable.gif";
					else
						$get("toolMenuReportIcon").src = "Images/48_Reports.png";
						
					//setup the report for viewing even if one may not be available
					GetSeriesReport(false);	
				}
			else{
				imageComp.ParseImgNum(imgNum);
				//windowLevelComp.WindowLevelDefaults();	//restore WL to thumbs
					if($get('ImgCompDiv') == null)
					AddPrimaryElement();
					
					//check to see if a report is marked available in the PACS database
					if(reportComp.get_hasReport())
						$get("toolMenuReportCompIcon").src = "Images/48_ReportAvailable.gif";
					else
						$get("toolMenuReportCompIcon").src = "Images/48_Reports.png";
						
					//get the setup the report for viewing even if one may not be available
					GetSeriesReport(true);
				}
		}
		this.ShowTabsAndTools();
		
		if(!this.get_IsComparison()){
			RemoveImgElement($get('ImgPrimDiv'));	//remove all childNodes, so I can add new ones accordingly
			GetWindowLevelPresets(null, false)	//ajax method that loads the Window/level dropdown list
			}
		else{
			RemoveImgElement($get('ImgCompDiv'));	//remove all childNodes, so I can add new ones accordingly
			GetWindowLevelPresets(null, true)	//ajax method that loads the Window/level dropdown list
			}
			this.LoadImages();	
	}
		
	//LoadImages()
	this.LoadImages = function(){
		var viewerSize; 
		var layout = layOut;
		var imagecache = imageCache;
		var name ='ImgPrim';		//this it the top level naming convention, all object below the 'ImgPrimDiv' or 'ImgCompDiv' will start with this name
	
	
		//Do a quick check to ensure that no tools are currently active and if so inactivate them
		if(!this.get_IsComparison()){
			if(measure.MeasureOn())
				measure.MeasureModeOFF();
			if(zoom.ZoomOn())
				zoom.ZoomMode(999);	///999 is the value for off, I use numbers in order to support dynamic passing of the order of magnification
		}
		else{
			if(measureComp.MeasureOn())
				measureComp.MeasureModeOFF();
			if(zoom.ZoomOn())
				zoomComp.ZoomMode(999);	///999 is the value for off, I use numbers in order to support dynamic passing of the order of magnification
		}
		
		try{
	
			if(!this.get_IsComparison())
				viewerSize =  $get('ImgPrimDiv');	
			else{
				viewerSize =  $get('ImgCompDiv');
				layout = layOutComp;
				imagecache = imageCacheComp;
				name = 'ImgComp';
			}
			
			this.set_ImgHeight(Math.round(viewerSize.clientHeight /layout.get_Rows()));
			this.set_ImgWidth(Math.round((viewerSize.clientWidth - (10*layout.get_Cols())) /layout.get_Cols()));
			
			
			//make sure the image exists in the series
			for(var i = 0; i<=this.get_SeriesImgs().length - 1; i++)
			{
				if(this.get_SeriesImgs()[i]  == this.get_ImgNum()){
					this.set_CurrentArrayIndex(i);
					break;
					}
			}
			this.FillImagePlaceHolder(layout.get_Rows(), layout.get_Cols(), this.GenerateLayOut(layout.get_Rows(), layout.get_Cols(), name));
			this.AddOverLayDiv(this.get_SeriesImgs()[i], name+"DivTable");	
			
			var cache = this.get_IsComparison() ? imageCacheComp : imageCache;
			cache.LoadCacheImages(this);
			
		}
		catch(ex){/*do not do anything, on a rare occassion if scrolling really frantically an random useless error occurs*/}
		
	}
	
	//Update the Labels
	this.UpdateLabels = function(){
		if(this.get_CompressionRatios().length != 0){
		
			var labelType = this.get_IsComparison()? 'ImgCompDiv' : 'ImgPrimDiv';
			var pagingObj = this.get_IsComparison() ? imagePagingComp : imagePaging;
		
			//get the labels currently being displayed
			for(var i=0; i<this.get_ImagesOnScreen().length; i++){
				if(this.get_CompressionRatios() [this.get_ImagesOnScreen()[i]] != null){
					var label = $get(String.format("{0}{1}{2}{3}", labelType, i, 'overLayDetails', this.get_ImagesOnScreen()[i]));
						var num = Number.parseInvariant(this.get_CompressionRatios() [this.get_ImagesOnScreen()[i]]);
						num *= 10;
						num = Math.round(num);
						num /= 10; 
						if(label != null)
							label.innerHTML = "<a>" + String.format(image.get_StringImageCompressed(), String.format("{0}{1}{2}",num,':',1)) + "</a>";
					}
			}
			
			Array.clear(this.get_ImagesOnScreen());
			//trace(this.get_ImagesOnScreen().length);
		}	
	}
	
	
	//Synchronize thumbnails
	this.SynchronizeThumbs = function(){
		var pagingObj = this.get_IsComparison() ? imagePagingComp : imagePaging;
		var pageFound = false;
		
		pagingObj.ResetPagesArray();
		
		if(pagingObj.get_Pages().length != 0){
			for(var i=0; i<pagingObj.get_Pages().length; i++){
				for(var k=0; k<pagingObj.get_ImagePlaceHoldersCount(); k++){
					if(this.get_ImgNum() == pagingObj.get_Pages()[i][k]){
						pagingObj.set_CurrentPage(i);
						pageFound = true;
						break;
					}
				}
				if(pageFound)
					break;
			}
		}
		pagingObj.RefactorPagingPages();
		pagingObj.Pages(this.get_ImgNum());
		pagingObj.CreateThumbs();
		pagingObj.ShowPrevious();
		pagingObj.ShowNext();
	}
	//LoadStudySeries - generate the table and images shown in the Study thumbnail selector
	this.LoadStudySeries = function(){
	
		//send an async call to the server to create and audit entry for this user and study
		//AuditLogging(this.get_IsComparison(),'Study opened', null); 

		
			//check to see if the div holding the thumbs contains an list of thumbs, if so remove it
				if($get('SeriesThumbsDiv').hasChildNodes())
					$get('SeriesThumbsDiv').removeChild($get('SeriesThumbs'));		//if div already exists, destroy it and regenerate a new list as to not have children image issues
			
		if(this.get_AllStudySeries().length == 1)
					$get('StudySeriesThumbStrip').style.display = 'none';
		
		if(this.get_AllStudySeries().length > 1){
				// creates a <dl>
			var dl = document.createElement("dl");
				dl.setAttribute("id", "SeriesThumbs");

			// creating all list items <dt> and <img>
			for(var i = 0; i <this.get_AllStudySeries().length; i++){		
					var dt = document.createElement("dt");
					var divLabels = document.createElement("a");
						divLabels.setAttribute("id","SeriesLabel"+i);
						dt.appendChild(divLabels);
					var Image = document.createElement("img");
					Image.setAttribute("id",'StudySeriesThumbStripimg'+i);
					//set the onerror event handler to point to imgErrorHandler
					//Image.setAttribute(' ', imgErrorHandler);
					//add the <dt> and <img> to the <dl>
					dt.appendChild(Image);
					dl.appendChild(dt);
			}
			
			//add the entire <ul> to the div that holds it 
			$get('SeriesThumbsDiv').appendChild(dl);
			//sets the style with positioning
			$get("SeriesThumbs").className = "SeriesThumbs";
	        
			var imageObj = this.get_IsComparison() ? imageComp : image;  //I have to do this because when I  can't call 'this' when I set the onclick event 
																		 //because it pertains to the Global Document and not to the instance of the image object
			  for(var i = 0; i <this.get_AllStudySeries().length; i++){
					//use the MS library to not set some of the img attributes and properties
					var img = getObject('StudySeriesThumbStripimg'+i,Sys.Preview.UI.Image);
					img.set_width(ThumbNailWidth);
					
					//this is the case because on occasion the series, study or something will contain a '+' and this gets striped out in the request and replaced with a blank which results in an error
					var series = this.get_AllStudySeries()[i];
					if(this.get_AllStudySeries()[i].indexOf('+') != -1)
						series = this.get_AllStudySeries()[i].replace('+','%2B');
	
					
					img.set_imageURL("JpegGenerator.ashx?seriesNum=" + series + '&imgNum=' + this.get_AllSeriesMiddleImg()[i] + "&Width=175 &Height=201" + "&Window=" + imageObj.get_Window() + "&Level=" + imageObj.get_Level()+ "&Thumb=true");
					img.get_element().longDesc = this.get_AllStudySeries()[i];
					
					//add event handlers to the click, mouseover, mouseout events
					$addHandler($get('StudySeriesThumbStripimg'+i), "click", function(){SwitchSeries(this)});
					$addHandler($get('StudySeriesThumbStripimg'+i), "mouseover",function(){enlarge(this, "thumb")});
					$addHandler($get('StudySeriesThumbStripimg'+i), "mouseout", function(){reduce(this, "thumb")});
					
					//mark the thumb of the current active series
					if(this.get_AllStudySeries()[i] == this.get_SeriesNum())
						$get('StudySeriesThumbStripimg'+i).style.border ="solid thin #ff6600";
					
					//add the first 6 letters of the series description to the thumbnail
					$get("SeriesLabel"+i).innerHTML += "<div style='position:relative; font-size:small; color:#7fb714; '>"+imageObj.get_SeriesDesc()[i].substr(0,10);+"</div>";
						
				}
				//make sure the thmb strip is visible and position it to be center screen
				$get('StudySeriesThumbStrip').style.display = '';
				
				$get('StudySeriesThumbStrip').style.top = (((g_resizer.clientHeight-topMargin) -(this.get_AllStudySeries().length *70))/2) > 0? (((g_resizer.clientHeight-topMargin) -(this.get_AllStudySeries().length *65))/2)+ 'px' : 0+'px';
			}
			
		}
	
	//LoadSeriesImages
	this.LoadSeriesImages = function(){
		var pagingObj = this.get_IsComparison() ? imagePagingComp : imagePaging;
		pagingObj.ResetPagesArray();
		pagingObj.Pages();
		pagingObj.CreateThumbs();
		pagingObj.ShowPrevious();
		pagingObj.ShowNext();
	}
	
	//--------internal methods--------
	//PreviousPage - called when user clicks on the go-previous.gif
	this.PreviousPage = function(e){
		pagingObj = this.get_IsComparison() ? imagePagingComp : imagePaging;
		pagingObj.set_UpdateViewerImages(false);
		pagingObj.ShowPrevious(e);	
		pagingObj.ShowNext();
		pagingObj.CreateThumbs();	
	}
	
	//NextPage - called when user clicks on the go-next.gif
	this.NextPage = function(e){
		pagingObj = this.get_IsComparison() ? imagePagingComp : imagePaging;
		pagingObj.set_UpdateViewerImages(false);
		pagingObj.ShowNext(e);	
		//this.LoadSeriesImages();
		pagingObj.CreateThumbs();
		pagingObj.ShowPrevious();
		//pagingObj.ShowNext();
	}
	
	this.DisplayOptionMarker = function(id){
		var comp = this.get_IsComparison() ? 'Comp' : '';
		$get('2X2'+ comp).style.visibility = 'hidden';
		$get('1X2'+ comp).style.visibility = 'hidden';
		$get('1X1'+ comp).style.visibility = 'hidden';
		//now show the one I want
		$get(id + comp).style.visibility = 'visible';
	}
	
	
	//AddOverLayDiv
	this.AddOverLayDiv = function(imgNum, name){
		//Array.clear(this.get_ImagesOnScreen());
		
		if(this.ShowLabels()){
			for(var i = 0; i <this.get_AllStudySeries().length; i++){
				if(this.get_AllStudySeries()[i] == this.get_SeriesNum())
					this.set_CurrentSeriesDesc(this.get_SeriesDesc()[i]);
				}
			
			var rows = $get(name).childNodes[0].childNodes.length;
			//loop through the rows and cols and add the img src to the image elements
			for(var i = 0; i <rows; i++){	
				for(var j = 0; j <$get(name).childNodes[0].childNodes[i].childNodes.length; j++){
					
					//this is so the image numbering is always correct
					var currentImageIndex = i>0 ? this.get_CurrentArrayIndex() + (1 * $get(name).childNodes[0].childNodes[i].childNodes.length): this.get_CurrentArrayIndex(); 
					//make sure that the image exist in the series array, to prevent indexing issues
								
						  if(currentImageIndex + j <= this.get_SeriesImgs().length - 1){
							
								var divWidth = this.get_ImgWidth() > this.get_ImgHeight() ? this.get_ImgHeight() : this.get_ImgWidth();
								var labelID = $get($get(name).childNodes[0].childNodes[i].childNodes[j].id + 'overLayDetails').id;
								//Array.add(this.get_ImagesOnScreen(),this.get_SeriesImgs()[currentImageIndex+j])
									
								$get($get(name).childNodes[0].childNodes[i].childNodes[j].id + 'overLayDetails').className ='Labels';
								
								$get($get(name).childNodes[0].childNodes[i].childNodes[j].id + 'overLayDetails').innerHTML += 	
																						"<div style='background-color:Transparent; float:left;  top:0;  Left:5; width:"+(parseInt(divWidth)-5) +"px;'>" +
																						"<div style='background-color:Transparent; float:left;  top:0;  Left:0; text-align:left;'>"+
																						"<div id='"+String.format("{0}{1}",labelID, this.get_SeriesImgs()[currentImageIndex+j])+"'style='background-color:Transparent; float:left; position:absolute;  top:"+(parseInt(this.get_ImgHeight())-20) +"px; Left:5px; text-align:left;'></div>"+
																						"<a style='color:#7fb714; '>" + this.get_FullName()+"</a><br>"+
																						"<a>ID: "+ this.get_PatientID()+' ('+ this.get_PatientGroup()+")</a><br>"+
																						"<a style='text-align:left;  color:#7fb714; '>DOB: "+ this.get_DOB()+"</a><br><br></div>"+
																						"<div style='background-color:Transparent; float:right;  top:0;  right:5; text-align:right;'>"+
																						"<a style=' color:#7fb714; '>"+ this.get_CurrentSeriesDesc() +"</a><br>"+
																						"<a >Acc: "+ this.get_Accession()+"</a><br>"+
																						"<a style=' color:#7fb714;'>IM# "+ this.get_SeriesImgs()[currentImageIndex+j] +"</a><br></div>"+
																						"<img style='position:relative; float:left; left:5px; cursor:hand;"+ this.HasVoiceAnnotation(this.get_SeriesImgs()[currentImageIndex+j])+"' title='Voice annotation available' src='Images/voice16.png' id='reportAvailable' runat='Server' onclick='annotation.SetUpAnnotationDiv("+ this.get_SeriesImgs()[currentImageIndex+j]+","+this.get_IsComparison() +");' />";
																						
							
							} 
								
						else {
							   $get(name).childNodes[0].childNodes[i].childNodes[j].id.style.visibility = 'false';
			 				 }
				}
			}
		}
	
	}
	
	this.AddScrollingLabelOnly = function(name, imageNum){
		if(this.ShowLabels()){
		
			var divWidth = this.get_ImgWidth() > this.get_ImgHeight() ? this.get_ImgHeight() : this.get_ImgWidth();
			$get(name +this.get_Position()+'overLayDetails').innerHTML = 	
									"<div style='background-color:Transparent; float:left;  top:0;  Left:5; width:"+(parseInt(divWidth)-5) +"px;'>" +
									"<div style='background-color:Transparent; float:left;  top:0;  Left:5; text-align:left;'>"+
									"<a style='color:#7fb714; '>" + this.get_FullName()+"</a><br>"+
									"<a>ID: "+ this.get_PatientID()+' ('+ this.get_PatientGroup()+")</a><br>"+
									"<a style='text-align:left;  color:#7fb714; '>DOB: "+ this.get_DOB()+"</a><br><br></div>"+
									"<div style='background-color:Transparent; float:right;  top:0;  right:5; text-align:right;'>"+
									"<a style=' color:#7fb714; '>"+ this.get_CurrentSeriesDesc() +"</a><br>"+
									"<a >Acc: "+ this.get_Accession()+"</a><br>"+
									"<a style=' color:#7fb714;'>IM# "+ imageNum +"</a><br></div>";
		}
	
	}
	
	
	//GenerateLayOut - adds a table with the corresponding rows and cols to the parent
	this.GenerateLayOut = function(rows, cols, name){
						
		if($get(name+"Div").hasChildNodes())
			$get(name+"Div").removeChild($get(name+"DivTable"));	
		// creates a <table> element and a <tbody> element
		var table = document.createElement("table");
		table.setAttribute("id", name+"DivTable");
		var body = document.createElement("tbody");
		var position = 0;
			for(var i = 0; i <rows; i++){	
				var tr = document.createElement("tr");
				for(var j = 0; j <cols; j++){		
					// Create a <td> element and a text node, make the text
					// node the contents of the <td>, and put the <td> at
					// the end of the table row
					var td = document.createElement("td");
						td.setAttribute("id", name+'Div'+position)	
					var divLabels = document.createElement("div");
						divLabels.setAttribute("id",name+'Div'+position+'overLayDetails');
						td.appendChild(divLabels);
					var divMeasure = document.createElement("div");
						divMeasure.setAttribute("id",name+'Div'+position+'Measure');
						td.appendChild(divMeasure);
					var Image = document.createElement("img");
					Image.setAttribute("id",name+position);
					//Image.setAttribute('onerror', imgErrorHandler);
					Image.onload = imgLoadedHandler;
					
					//$addHandler(Image, 'onload', imgLoadedHandler);
					td.appendChild(Image);
					/*var imgZoom = document.createElement("div");
						imgZoom.setAttribute("id",name+'Div' + position +'MagnificationDiv');
					td.appendChild(imgZoom);*/
					tr.appendChild(td);
					position++;
				  }
				//add the row to the table
				body.appendChild(tr);
			}
		// appends <table> to the parent object
		table.appendChild(body);
		$get(name+'Div').appendChild(table);
		return name+"DivTable";
	}

/*	
*function FillImagePlaceHolder()
*
* generates the images and places them in the previous created placeholders
*/
this.FillImagePlaceHolder = function(rows, cols, name){
	var totalPositions = rows * cols;
	var countToFirst = this.get_CurrentArrayIndex();
	var countToLast = this.get_SeriesImgs().length - this.get_CurrentArrayIndex();

	
	if((totalPositions - this.get_Position()) >= countToLast){
		if(this.get_SeriesImgs()[(this.get_SeriesImgs().length) - totalPositions] != null){
			this.set_ImgNum(this.get_SeriesImgs()[(this.get_SeriesImgs().length) - totalPositions]);
			this.set_CurrentArrayIndex(this.get_ImgArrayLocation());
		}
	}
	
	
	/*determines which way the server will resize the image, 
	 * so that it will fit in the div size.  I only need to 
	 * call this because before the images are placed in their 
	 * appropriate div's I place a background loading.gif as the 
	 * background image in the center of the image placeholder
	*/
	
	this.CalibrateImageSize();

	//this is so the image numbering is always correct
	Array.clear(this.get_PreLoadedImgNums());
		
	//loop through the rows and cols and add the img src to the image elements
		for(var i = 0; i <rows; i++){	
			for(var j = 0; j <cols; j++){	
				var img = getObject($get(name).childNodes[0].childNodes[i].childNodes[j].childNodes[2].id, Sys.Preview.UI.Image);	//childNode[0] - overlayDiv, childNode[1] - MeasureDiv, childNode[2] - div placeholder for magnification image, childNode[3] - actualImage,
				img.addCssClass('ImgPosition0');
		
				//set up the background image with the loading.gif and place it in the center

//				img._element.height = this.get_ImgHeight();
//				img._element.width = this.get_ImgWidth();
				img._element.style.backgroundImage = "url(Images/loading.gif)";
				img._element.style.backgroundPosition = "center";
				img._element.style.backgroundRepeat = "no-repeat";
			
				
				var currentImageIndex = i>0 ? this.get_CurrentArrayIndex() + (1 * cols): this.get_CurrentArrayIndex(); 
				//verify the image exist prior to fetching from the server
					if(this.get_SeriesImgs()[currentImageIndex+j] == null){
						//remove the image from the placeholder so that an unloadable image does not present itself in its place
						$get(name).childNodes[0].childNodes[i].childNodes[j].childNodes[2].parentNode.removeChild($get(name).childNodes[0].childNodes[i].childNodes[j].childNodes[2]);
					
						}
					else{
						
						//Image numbers being requested
						if(!Array.contains(this.get_ImagesOnScreen(), this.get_SeriesImgs()[currentImageIndex+j]))
							Array.add(this.get_ImagesOnScreen(), this.get_SeriesImgs()[currentImageIndex+j]);
						
						var URL = "";
						//fetch the images from the httphandler on the server
						if(this.get_IsComparison()){
							    URL = (String.format("{0}{1}{2}{3}{4}{5}{6}{7}{8}{9}{10}{11}{12}{13}{14}", 
															"JpegGenerator.ashx?seriesNum=", this.get_SeriesNum(), 
															'&imgNum=', this.get_SeriesImgs()[currentImageIndex+j], 
															"&Width=", this.get_ImgWidth(), 
															"&Height=", this.get_ImgHeight(), 
															"&Window=", this.get_Window(), 
															"&Level=", this.get_Level(), 
															"&TimeStamp=", this.get_TimeStamp(),
															"&SplitScreenSeries=Series2"));
													
								Array.add(this.get_PreLoadedImgNums(), String.format("{0}", this.get_SeriesImgs()[currentImageIndex+j]));
								img.set_imageURL(URL);
							}
						else {
							    URL = (String.format("{0}{1}{2}{3}{4}{5}{6}{7}{8}{9}{10}{11}{12}{13}",
															"JpegGenerator.ashx?seriesNum=", this.get_SeriesNum(),
															"&imgNum=", this.get_SeriesImgs()[currentImageIndex+j], 
															"&Width=", this.get_ImgWidth(),
															"&Height="+this.get_ImgHeight(), 
															"&Window=" + this.get_Window(), 
															"&Level=" + this.get_Level(),
															"&TimeStamp=", this.get_TimeStamp()));
															
								Array.add(this.get_PreLoadedImgNums(), String.format("{0}", this.get_SeriesImgs()[currentImageIndex+j]));
								img.set_imageURL(URL);
							}
						
						img.get_element().longDesc = i+':'+j;
						
					}	
			}
		}
	
	}
	
	//ScrollImage
	
	this.ScrollImage = function(){
		var viewerSize; 
		var layout = layOut;
		var i = 0;
		var name = 'ImgPrimDiv';
	
			if(!this.get_IsComparison())
				viewerSize =  $get('ImgPrimDiv');	
			else{
				viewerSize =  $get('ImgCompDiv');
				layout = layOutComp;
				name = 'ImgCompDiv';
			}
//////			this.set_ImgHeight(viewerSize.clientHeight/layout.get_Rows());
//////			//this.set_ImgWidth(viewerSize.clientWidth /layout.get_Cols());
//////			this.set_ImgWidth((viewerSize.clientWidth - (10*layout.get_Cols())) /layout.get_Cols());

				
			//make sure the image exists in the series
			for(; i<=this.get_SeriesImgs().length - 1; i++)
			{
				if(this.get_SeriesImgs()[i]  == this.get_ImgNum()){
					this.set_CurrentArrayIndex(i);
					break;
					}
			}
			//trace("Start " + new Date().getSeconds() +":" + new Date().getMilliseconds());
			var img = getObject(this.get_ActiveImg(), Sys.Preview.UI.Image);
			img._element.onload = ScrollingOnComplete;
			//trace("End " + new Date().getSeconds() +":" + new Date().getMilliseconds());
			//make sure that the image exist in the series array, to prevent indexing issues
					if(i + 1 <= this.get_SeriesImgs().length){

						if(this.get_IsComparison())
							img.set_imageURL(String.format("{0}{1}{2}{3}{4}{5}{6}{7}{8}{9}{10}{11}{12}{13}{14}",
															"JpegGenerator.ashx?seriesNum=",this.get_SeriesNum(),
															"&imgNum=",this.get_SeriesImgs()[i],
															"&Width=",this.get_ImgWidth(),
															"&Height=",this.get_ImgHeight(),
															"&Window=",this.get_Window(),
															"&Level=", this.get_Level(),
															"&TimeStamp=", this.get_TimeStamp(),
															"&SplitScreenSeries=Series2"));
						else
							img.set_imageURL(String.format("{0}{1}{2}{3}{4}{5}{6}{7}{8}{9}{10}{11}{12}{13}",
															"JpegGenerator.ashx?seriesNum=",this.get_SeriesNum(),
															"&imgNum=",this.get_SeriesImgs()[i],
															"&Width=",this.get_ImgWidth(),
															"&Height=",this.get_ImgHeight(),
															"&Window=",this.get_Window(),
															"&Level=",this.get_Level(),
															"&TimeStamp=", this.get_TimeStamp()));
						this.AddScrollingLabelOnly(name, this.get_SeriesImgs()[i]);
						this.set_ImgNum(this.get_SeriesImgs()[i]);
	
					}
			
	}
	

	//
	this.CalibrateImageSize = function(){
		
		var scaling;
		var scalingW = this.get_ImgWidth() / this.get_DicomImgWidth();  //scale ratio comparison of divWidth to Image width 
		var scalingH = this.get_ImgHeight() / this.get_DicomImgHeight();  //scale ratio comparison of div Height to Image Height
		if(scalingH > scalingW)
			scaling = scalingW;
		else
			scaling = scalingH;
			
		this.set_ResizeRatio(scaling);
		trace(this.get_ResizeRatio());

		//now determine if I will need to scale larger of smaller to fit in the div tag
		// if scale is greater than 1 the image is smaller than the div and will need to 
		// multiplied by the scale to maximize window usage 
		if(scalingH > 1 && scalingW > 1) {
			this.set_ImgWidth(Math.round(this.get_DicomImgWidth() * scaling));
			this.set_ImgHeight(Math.round(this.get_DicomImgHeight() * scaling));
		}
		else {
			if(scaling < 1) {
				this.set_ImgWidth(Math.round(this.get_DicomImgWidth() * scaling));
				this.set_ImgHeight(Math.round(this.get_DicomImgHeight() * scaling));
				this.set_ScalingArithmeticOperation('Multiplication');
			}
			else {
				this.set_ImgWidth(Math.round(this.get_DicomImgWidth() / scaling));
				this.set_ImgHeight(Math.round(this.get_DicomImgHeight() / scaling));
				this.set_ScalingArithmeticOperation('Division');
			}
		}
	}
	
}

/*****************************************************
* Paging Class Object
******************************************************/
function Paging(value){
	//--------internal variables--------
	var _isComparison = value;
	var _pages = new Array();
	var _currentPage = null;
	var _imagePlaceHolders;
	var _startPage = null;
	var _startPageImage;
	var _endPage = null;
	var _pagingListName;
	var _defaultThumbNailCount = 5;
	var _updateViewerImages = true;		//determines whether the viewers images need to be updated 
	
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_ImagePlaceHoldersCount = function(){
		return _imagePlaceHolders;
	}
	this.set_ImagePlaceHoldersCount = function(value){
		_imagePlaceHolders = value;
	}
	this.get_CurrentPage = function(){
		return _currentPage;
	}
	this.set_CurrentPage = function(value){
		_currentPage = value;
	}
	this.get_StartPage = function(){
		return _startPage;
	}
	this.set_StartPage = function(value){
		_startPage = value;
	}
	this.get_StartPageImage = function(){
		return _startPageImage;
	}
	this.set_StartPageImage = function(value){
		_startPageImage = value;
	}
	this.get_EndPage = function(){
		return _endPage;
	}
	this.set_EndPage = function(value){
		_endPage = value;
	}
	this.get_PagingListName = function(){
		return _pagingListName;
	}
	this.set_PagingListName = function(value){
		_pagingListName = value;
	}
	this.get_Pages = function(){
		return _pages;
	}
	this.set_Pages = function(value){
		_pages = value;
	}
	this.get_UpdateViewerImages = function(){
		return _updateViewerImages;
	}
	this.set_UpdateViewerImages = function(value){
		_updateViewerImages = value;
	}
	
	
	this.ResetPagesArray = function(){
		var newArray = new Array();
		_pages = newArray;
		_currentPage = null;
	}
	
	this.IsLastThumbnail = function(){
		return this.get_EndPage() != _pages.length-1 ? false:true;
	}
	
	this.IsFirstThumbnail = function(){
		return this.get_StartPage()!= 0 ? false:true;
	}
	
	
	//method : Pages
	//purpose : on the initial series open it sets up the paging pages based on the middle image of the series.  This is only used on the inital opening
	//		  : and is continued to be used if the users scrolls via the previous page and next page buttons.  If scrolling via mouse is used then RefactorPagingPages
	//		  : is used from that point on.
	this.Pages = function(currentImg){
		var imageObj = this.get_IsComparison() ? imageComp : image;
		var layOutObj = this.get_IsComparison()? layOutComp : layOut;
	
		if(currentImg == null){
			this.set_ImagePlaceHoldersCount(layOutObj.get_Cols() * layOutObj.get_Rows());
			var pages = 0;
			for(var i=0; i<imageObj.get_SeriesImgs().length; i++){
				if(i == 0 || i%this.get_ImagePlaceHoldersCount() == 0){
				  var Page = new Array();
				  var k=0;
					for(var j=i; j<this.get_ImagePlaceHoldersCount()+i; j++){
 						Page[k] = imageObj.get_SeriesImgs()[j];
 						k++;
					}
				   _pages[pages] = Page;
				   pages++; 
				}		
			}
			
		}
		this.SetMiddlePage();
	}	
	//method : RefactorPagingPages
	//purpose : after the users scrolls the images and then releases, I then need to refactor all the paging pages so that the thumbs now correspond to the images shown
	this.RefactorPagingPages = function(){
		var imageObj = this.get_IsComparison() ? imageComp : image;
		var i = imageObj.get_CurrentArrayIndex();
	
		if(parseFloat(imageObj.get_CurrentArrayIndex() / this.get_ImagePlaceHoldersCount()) == 0)
			_currentPage =  0;
		else{
			if(this.get_ImagePlaceHoldersCount() > imageObj.get_SeriesImgs().length){
				_currentPage = 0;
				i = 0;
			}
				//trace('Index: '+imageObj.get_CurrentArrayIndex() + ' / ' +'PlaceHoldersCount: '+this.get_ImagePlaceHoldersCount());
			else if((imageObj.get_CurrentArrayIndex() % this.get_ImagePlaceHoldersCount()) != 0 )
				_currentPage = parseInt(imageObj.get_CurrentArrayIndex()/ this.get_ImagePlaceHoldersCount()+1);
			else
				_currentPage = parseInt(imageObj.get_CurrentArrayIndex()/ this.get_ImagePlaceHoldersCount());
			}
		var pageCounter = _currentPage;
		var lastPage;
		var page = new Array();
			//trace("*******************************************************");
		for(;;){//trace('page['+pageCounter+'] ' + '  Image: ' + imageObj.get_SeriesArray().slice(i, i+this.get_ImagePlaceHoldersCount()));
			if(i + this.get_ImagePlaceHoldersCount() > imageObj.get_SeriesImgs().length-1){
				page[pageCounter] = imageObj.get_SeriesArray().slice(i);
				break;
			}
			else 
				page[pageCounter] = imageObj.get_SeriesArray().slice(i, i+this.get_ImagePlaceHoldersCount());
				
			i = i+this.get_ImagePlaceHoldersCount();
			pageCounter++;	
		}
		
		//now subtract one from the middle page since it is the page before it
		if(_currentPage - 1 >= 0){
			pageCounter = _currentPage -1;	
			//if(pageCounter > 0){
			
				var j = imageObj.get_CurrentArrayIndex();
				for(;;){
					//trace('page['+pageCounter+'] ' + '  Image: ' + imageObj.get_SeriesArray().slice(j - this.get_ImagePlaceHoldersCount(), j));
					if(j - this.get_ImagePlaceHoldersCount()<=0){
						var k = this.get_ImagePlaceHoldersCount();
						
							for(;k>0; k--){
								if(imageObj.get_SeriesImgs()[k] == null)
									break;
							}
					
						page[pageCounter] = imageObj.get_SeriesArray().slice(0, j-k);
						break;
					}
					else
						page[pageCounter] = imageObj.get_SeriesArray().slice(j - this.get_ImagePlaceHoldersCount(), j);
						
					j = j-this.get_ImagePlaceHoldersCount();
					pageCounter--;
					
					if(pageCounter < 0 || j <0)
						break;
				}
		 }
		
		_pages = page;
		this.ReorderPagingPages();
	}
	
	//method : ReorderPagingPages
	//purpose : this function puts the paging thumbnails pages in decreasing order
	this.ReorderPagingPages = function(){
		var tempArray = new Array();
		for(var k=0; k<_pages.length; k++)
			tempArray[k] = 0;

		for(var i in _pages){
			for(var j=0; j<_pages.length;j++){
				if(j == parseInt(i))
					tempArray[j] = _pages[i];
			}
		}
		_pages = tempArray;
	}
	
	//method : SetMiddlePage
	//purpose :  Sets up the middle thumbnail as the active one, and sets up the startPage, currentPage(middlePage) and the endPage
	this.SetMiddlePage = function(){
	
		if(_currentPage == null)
			_currentPage = _pages.length % 2 == 0 ? _pages.length/2 : (_pages.length - 1) / 2;
		//javascript tracing tool
		//trace('SetMiddlePage -- ' +' CurrentPage ' + _currentPage);		
		var imageObj = this.get_IsComparison() ? imageComp : image;
		
		//now determine how many thumbs I will need to be showing
		imageObj.set_ThumbNailCount(_pages.length > imageObj.get_DefaultThumbNailCount() ? imageObj.get_DefaultThumbNailCount() : _pages.length);
		
		//imageObj.set_ImgNum(_pages[_currentPage][0]);
		imageObj.set_MiddleImage(_pages[_currentPage][0]);
		
		
		_startPage = _currentPage - 2;
			if( _startPage <= 0){
				_startPage = 0;
				_endPage = imageObj.get_ThumbNailCount()-1;
				return;
				}
				
		_endPage = _currentPage + 2;
			if(_endPage >=_pages.length -1){
				_endPage = _pages.length -1;
				_startPage = _endPage - (imageObj.get_ThumbNailCount()-1);
				return;
				}
	}

	//method : CreateThumbs
	//purpose :  creates the table and places the images in it
	this.CreateThumbs = function(){
        var action;		
        var domRefObject;		//parent reference of the div that serves as a place holder for where the thumbs will be		
        var listName;		
        var paging = false;		//true if paging event else false
        var divHolder;		//this is a place holder for the thumbnail strips
        var thumbPageCount		//the # of thumbs that will be needed created to handle the size of the current series
        var layoutObj;		
        var middlePage;
        var startPageImageIndex;
        var imgNumLabel;
        
		//check to see if this is a comparison object
		if(this.get_IsComparison()){		//I have to do this because when I  can't call 'this' when I set the onclick event 
           imageObj = imageComp;			//because it pertains to the Global Document and not to the instance of the image object.
           action='Series2';
           divHolder = 'CompThumb';
           domRefObject='CurrentSeriesThumbsDivComp';
           this.set_PagingListName("CurrentSeriesThumbsComp");
           imgNumLabel = 'Series2';
           
         } 
         else{
			imageObj = image;
            action= "Series1";
            domRefObject='CurrentSeriesThumbsDiv';
            this.set_PagingListName("CurrentSeriesThumbs");
            divHolder = 'PrimaryThumb';
            imgNumLabel = 'Series1';
         }
         
		//check to see if the div holding the thumbs contains an list of thumbs, if so remove it
		if($get(domRefObject).hasChildNodes())		
			$get(domRefObject).removeChild($get(_pagingListName));	//if div already exists, destroy it and regenerate a new div as to not have children image issues
			
			// creates a <dl>
			var dl = document.createElement("dl");
				dl.setAttribute("id", _pagingListName);
			
			// creating all list items <dt> and <img>
			for(var i = 0; i <imageObj.get_ThumbNailCount(); i++){		
					var dt = document.createElement("dt");
					dt.setAttribute("id","dt"+i);
					var divLabels = document.createElement("a");
						divLabels.setAttribute("id",imgNumLabel+i);
						dt.appendChild(divLabels);
					var Image = document.createElement("img");
					Image.setAttribute("id",_pagingListName+"Img"+i);
					//set the onerror event handler to point to imgErrorHandler
					//Image.setAttribute('onerror', imgErrorHandler);
					//add the <li> and <img> to the <ul>
					dt.appendChild(Image);
					dl.appendChild(dt);
			}
			
	
			//add the entire <ul> to the div that holds it
			$get(domRefObject).appendChild(dl);
			if(this.get_IsComparison())
				$get(_pagingListName).className = "CurrentSeriesThumbsDivComp";//"SeriesThumbs";
			
			for(var j=0; j<imageObj.get_ThumbNailCount();j++){
				//use the MS library to not set some of the img attributes and properties
                var img = getObject(_pagingListName+"Img"+j,Sys.Preview.UI.Image);
					img.set_width(ThumbNailWidth);
					
					//this is the case because on occasion the series, study or something will contain a '+' and this gets striped out in the request and replaced with a blank which results in an error
					var series = imageObj.get_SeriesNum();
					if(imageObj.get_SeriesNum().indexOf('+') != -1)
						imageObj.set_SeriesNum(imageObj.get_SeriesNum().replace('+','%2B'));
					
					//img.set_imageURL("JpegGenerator.ashx?img=" + imageObj.get_SeriesNum() +':'+(imagePaging.get_StartPageImage()+(j*this.get_ImagePlaceHoldersCount()))+"&action="+action +"&dim=" + "175X201" + "&WL=" + imageObj.GetWindowLevel());
					img.set_imageURL("JpegGenerator.ashx?seriesNum=" + imageObj.get_SeriesNum() + '&imgNum=' + (_pages[_startPage +j][0]) +  "&Width=175&Height=201" +"&SplitScreenSeries="+ action  + "&Window=" + imageObj.get_Window() + "&Level=" + imageObj.get_Level()+ "&Thumb=true");
					//add event handlers to the click, mouseover, mouseout events
					$addHandler($get(_pagingListName+"Img"+j), "click", function(){Selected(this)});
					$addHandler($get(_pagingListName+"Img"+j), "mouseover",function(){enlarge(this, "thumb")});
					$addHandler($get(_pagingListName+"Img"+j), "mouseout", function(){reduce(this, "thumb")});
					
					$get(_pagingListName+"Img"+j).lang = imageObj.get_SeriesNum()+':'+(_pages[_startPage +j][0]);
					
					if(imageObj.get_ImgNum() == 0 && imageObj.get_MiddleImage() == _pages[_startPage +j][0])
						img.get_element().style.border = "#ff6600 thin solid";
					
					else if(imageObj.get_ImgNum() == _pages[_startPage +j][0])
							img.get_element().style.border = "#ff6600 thin solid";
					else if(imageObj.get_ImgNum() == null &&(_startPage +j) == 0)
							img.get_element().style.border = "#ff6600 thin solid";
					
					
						//$get("dt"+j).style.border = "orange thin solid";
						//
//						$get(imgNumLabel+j).style.top = '17px';
//						$get(imgNumLabel+j).style.left = '4px';
//						$get(imgNumLabel+j).style.border = "white thin solid";
					
					var firstImage = _pages[_startPage +j][0];
					var lastImage = _pages[_startPage +j][(_pages[_startPage +j].length)-1];
						if(lastImage == null){
							for(var k=(_pages[_startPage +j].length)-1; k>=0; k--){
								if(_pages[_startPage +j][k] != null){
									lastImage = _pages[_startPage +j][k];
									break;
									}
								}
						}
				
					if(firstImage == lastImage)
						$get(imgNumLabel+j).innerHTML += "<div style='position:relative; font-size:small; color:#7fb714; '>"+firstImage+"</div>"; 
						
					else
						$get(imgNumLabel+j).innerHTML += "<div style='position:relative; font-size:small; color:#7fb714; '>"+firstImage+" - "+lastImage+"</div>";
						
		}
		
		//if true then update the main images to correspond with the Images thumbs
		if(_updateViewerImages){
			var img = null;
			if(imageObj.get_SeriesImgs()[imageObj.get_CurrentArrayIndex()] == null)
				img = imageObj.get_MiddleImage();
			else{
					//if(imageObj.get_MiddleImage() - imageObj.get_CurrentArrayIndex() != 1)
					if(imageObj.get_MiddleImage() !=imageObj.get_SeriesImgs()[imageObj.get_CurrentArrayIndex()])
						img = imageObj.get_SeriesImgs()[0];
						
					else
						img = imageObj.get_SeriesImgs()[imageObj.get_CurrentArrayIndex()];
				}
			
			imageObj.SetUpImg(imageObj.get_SeriesNum() +':'+ img);
			}
		_updateViewerImages =true;
		$get(divHolder).style.top = (((g_resizer.clientHeight -topMargin)-(imageObj.get_ThumbNailCount()*70))/2) + 'px';	//uncomment this section if you want the thumbs centered horizontally
		$get(divHolder).style.display='';

	}	
	
	//method : ShowPrevious
	//purpose : decides on whether to show the previous page icon
	this.ShowPrevious = function(e){
		
		if(e!=null){
			/*I do this here because if they have scrolled to the end and not paged and then click the previous
			 * arrow then I will need to check and see if _currentPage is the last page.  If it is then when the user 
			 * clicks the previous arrow button, I need to move the _currentPage to the middle otherwise they will have to click it several times*/
			if(_endPage ==_pages.length-1 && _currentPage == _pages.length -1)
			_currentPage = _startPage + (_endPage - _startPage)/2;
			
			if(_startPage -1 >= 0)
				_currentPage--;
				
			//reset the start and end pages since everything is driven off the _currentPage, and this page is currently being incremented to reflect the changes	
			this.set_EndPage(null);
			this.set_StartPage(null);
			this.SetMiddlePage();
		}
		$get(_pagingListName + 'go-previous').style.display = this.get_StartPage()!= 0 ? '':'none';
	}
	
	//method : ShowNext
	//purpose : decides on whether to show the next page icon 
	this.ShowNext = function(e){
		if(e!=null){
			/*I do this here because if they have scrolled to the beginning and not paged and then click the next
			 * arrow then I will need to check and see if _currentPage is the first page (usually 0).  If it is then when the user 
			 * clicks the next arrow button, I need to move the _currentPage to the middle otherwise they will have to click it several times*/
			if(_currentPage ==0 && _startPage == 0)
			_currentPage = (_endPage - _startPage)/2;
			
			//increment the currentpage if not at the last page
			if(_endPage +1 <= _pages.length)
				_currentPage++;
			
			//reset the start and end pages since everything is driven off the _currentPage, and this page is currently being incremented to reflect the changes	
			this.set_EndPage(null);
			this.set_StartPage(null)
			this.SetMiddlePage();
		}
		$get(_pagingListName + 'go-next').style.display = this.get_EndPage() != _pages.length-1 ? '':'none';
	}
	//method : ToggleSelectedPage
	//purpose : toggles on/off the selected page by setting a orange border on the selected page when clicked
	this.ToggleSelectedPage = function(element){
		var imageObj = this.get_IsComparison() ? imageComp : image;
		for(var j=0; j<imageObj.get_ThumbNailCount();j++){
			$get(_pagingListName+"Img"+j).style.border = '';
		}
		
		//now set the one recently selected to have the selected border	
		element.style.border = "#ff6600 thin solid";
	}
}



/*****************************************************
* ImageCache Class Object
******************************************************/
function ImageCache(value){
	//--------internal variables--------
	var _imgCache = new Array();
	var _imgCacheComp = new Array();
	var _cacheSize = 30;
	var _isComparison = value;
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_CacheArray = function(){
		if(this.get_IsComparison())
			return _imgCacheComp;
		else
			return _imgCache;
	}
	this.get_CacheSize = function(){
		return _cacheSize;
	}
	//--------methods--------
	this.RemoveCacheImgs = function(){
//		try{
//			var cacheType = this.get_IsComparison() ? 'CompImgCache' :  'PrimImgCache';
//			var parent = new Object();
//			parent = $get('CacheImg').parentNode;
//			parent.removeChild($get('CacheImg'));
//			var div = CreateDiv(parent.id, 'CacheImg', 'div', null);
//			div.style.position = 'relative';
//			div.style.top = "7px";
//			Array.clear(_imgCache);
//			}
//			
//			catch(ex){}
			
			try{
			var cacheArray = this.get_IsComparison() ? _imgCacheComp : _imgCache;
			var cacheType = this.get_IsComparison() ? 'CompImgCache' :  'PrimImgCache';
			var count = $get('CacheImg').childNodes.length -1;
			
				for(var i=count; i>=0; i--){
					$get('CacheImg').removeChild($get('CacheImg').childNodes[i]); 
				}
				Array.clear(cacheArray);
			}
			
			catch(ex){}
	}
	
	
	this.AddCacheImg = function(imgNum){
		var imageObj = this.get_IsComparison() ? imageComp : image;
		var cacheArray = this.get_IsComparison() ? _imgCacheComp : _imgCache;
		if(Array.contains(imageObj.get_SeriesImgs(), imgNum)){
			//trace(imgNum);
			var img = getObject(this.CreateCachePlaceHolder(imgNum), Sys.Preview.UI.Image);
			var url = this.UrlString(imgNum, imageObj);
			img.set_imageURL(url);
			Array.add(cacheArray, imgNum);
			//trace(imgNum);
		}
	}
	
	
	
		
	this.LoadCacheImages = function(){
		var scrolls;
		var imageObj = this.get_IsComparison() ? imageComp : image;
		var cacheArray = this.get_IsComparison() ? _imgCacheComp : _imgCache;
	 
				//load images after the current image into the cache
				 try{  
						//make sure that you don't try to load images that aren't there, especially cr's 
						if((imageObj.get_ImgArrayLocation() + this.get_CacheSize()/2) > imageObj.get_SeriesImgs().length){
								scrolls = imageObj.get_SeriesImgs().length - imageObj.get_ImgArrayLocation();
								if(imageObj.get_SeriesImgs().length < scrolls) 
									scrolls = imageObj.get_SeriesImgs().length;
							}
						else
							scrolls = this.get_CacheSize()/2;
							
						//prefetch the next images
						for(var j=1; j<scrolls; j++){
							if(imageObj.get_SeriesImgs()[imageObj.get_ImgArrayLocation()+j]!= null){
								var imgNum = imageObj.get_SeriesImgs()[imageObj.get_ImgArrayLocation()+j];
								if(!Array.contains(cacheArray, imgNum)){
									var img = getObject(this.CreateCachePlaceHolder(imgNum), Sys.Preview.UI.Image);
									var url = this.UrlString(imgNum, imageObj);
									img.set_imageURL(url);
									Array.add(cacheArray,imgNum);
									//trace(imgNum);
								}
							}
						}	
					 }
					catch(e){ }
				 try{
						//load images before the current image into the cache so if they scroll back in the series
						 //make sure that you don't try to load images that aren't there, especially cr's 
						if((imageObj.get_ImgArrayLocation() - this.get_CacheSize()/2) < 0){
								scrolls = imageObj.get_ImgArrayLocation();
								if(imageObj.get_SeriesImgs().length < scrolls) 
									scrolls = imageObj.get_SeriesImgs().length;
							}
						else
							scrolls = this.get_CacheSize()/2;
							
						for(var k=1; k<scrolls; k++){
								if(imageObj.get_SeriesImgs()[imageObj.get_ImgArrayLocation()-k]!= null){
								var imgNum = imageObj.get_SeriesImgs()[imageObj.get_ImgArrayLocation()-k];
								if(!Array.contains(cacheArray, imgNum)){
									var img = getObject(this.CreateCachePlaceHolder(imgNum), Sys.Preview.UI.Image);
									var url = this.UrlString(imgNum,imageObj);
									img.set_imageURL(url);
									Array.add(cacheArray, imgNum);
									//trace(imgNum);
								}
							}																			
										
						} 
					}
					catch(e){ }
		}
	
	//returns the image url string
	this.UrlString = function(imgNum, imageObj){	
		var url = String.format("{0}{1}{2}{3}{4}{5}{6}{7}{8}{9}{10}{11}{12}{13}",
					  "JpegGenerator.ashx?seriesNum=",imageObj.get_SeriesNum(),
					  "&imgNum=", imgNum,
					  "&Width=", imageObj.get_ImgWidth(),
					  "&Height=", imageObj.get_ImgHeight(),
					  "&Window=", imageObj.get_Window(), 
					  "&Level=", imageObj.get_Level(),
					  "&TimeStamp=", imageObj.get_TimeStamp());
					  
		if(this.get_IsComparison())
			url = String.format("{0}{1}", url,"&SplitScreenSeries=Series2"); 
		return url;
	}	
	
	
	// CreateCachePlaceHolder - returns a javascript image object
	this.CreateCachePlaceHolder = function(imgNum){
		var cacheType = this.get_IsComparison() ? 'CompImgCache' :  'PrimImgCache';
		//create the div that will hold the cached image
		var div = CreateDiv('CacheImg', 'Div' + cacheType+ imgNum, 'div', null);
		div.style.position = 'absolute';
		div.style.left = leftMargin+'px';
		div.style.top = (topMargin)+'px';
		
		//create the image that goes in div	
		var imgPlaceHolder = CreateDiv(div.id ,cacheType + imgNum,'img',null);
		//$get(cacheType + i).style.position = 'relative';
		imgPlaceHolder.style.position = 'relative';
		return imgPlaceHolder.id;
	}
	
	
	
	//--------internal methods--------
}




/*****************************************************
* Mouse Class Object
******************************************************/
function MouseEvents(value){
	//--------internal variables--------
		var _startLocationX = 0;
		var _startLocationY = 0;
		var _isComparison = value;
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}
	this.get_StartLocationX = function(){
		return _startLocationX;
	}
	this.set_StartLocationX = function(value){
		_startLocationX = value;
	}
	this.get_StartLocationY = function(){
		return _startLocationY;
	}
	this.set_StartLocationY = function(value){
		_startLocationY = value;
	}
}
	//--------methods--------
	function MouseDown(e){
		var domEventVar = new Sys.UI.DomEvent(e);
		var menu;
		if(e.target.id.indexOf('Prim') != -1)
			menu = 'PopuptoolMenu';
		if(e.target.id.indexOf('Comp') != -1)
			menu = 'PopuptoolMenuComp';
			
		switch(domEventVar.button){
			case Sys.UI.MouseButton.rightButton : {
													 if(e.target.id.startsWith('Img') && $get(e.target.id).tagName =="IMG"){
																										
														var wlObj = e.target.id.indexOf('Prim') != -1 ? windowLevel : windowLevelComp;
														
														//get the current mouse coordinates
														var mouseObj = e.target.id.indexOf('Prim') != -1 ? mouse : mouseComp;
														mouseObj.set_StartLocationX(e.clientX);
														mouseObj.set_StartLocationY(e.clientY);
														$addHandler(document,'mousemove', MouseMove); 
														$addHandler(document,'mouseup', MouseRelease);
														_activeSeriesScrolling = e.target.id.indexOf('Prim') != -1 ? 'Series1':'Series2';
														
														
														wlObj.set_ActiveImgID(e.target.id)
														wlObj.set_On(true);
														
														var imgObj = wlObj.get_IsComparison()? imageComp : image;
														var resizeRatio = imgObj.get_ResizeRatio();
															
														
														var currentImg = $get(wlObj.get_ActiveImgID());
														var height = null;
														var width = null;
													
														if( currentImg.width > currentImg.height){
															height = currentImg.height/(currentImg.height/100);
															width = currentImg.width/(currentImg.height/100);
															}
														else{
															height = currentImg.height/(currentImg.width/100);
															width = currentImg.width/(currentImg.width/100);
															}
															 
														// Create the clip window
														var factor = 50;
														
														var location = Sys.UI.DomElement.getLocation(currentImg);
														var x0 = e.clientX - location.x-factor;  //e.clientX - factor - leftMargin;
														var y0 = e.clientY - location.y-factor;   //e.clientY - factor - topMargin;
														
														
														var WLDiv = document.createElement('div');
														WLDiv.setAttribute('id', 'wlDiv');
														WLDiv.style.border = "Black 1px solid";
														WLDiv.style.position = 'absolute';
														// the -1 is used to compensate for the black border on the div
														WLDiv.style.top = String.format("{0}{1}", e.clientY - factor-1, "px");
														WLDiv.style.left =String.format("{0}{1}",e.clientX - factor-1,  "px");
														WLDiv.style.background = 'transparent';
														
							
														var img = document.createElement('img');
														img.width = width;
														img.height = height;
														img.setAttribute('id', 'WindowLevelingImg');
														img.style.display = 'none';
														WLDiv.appendChild(img);
														$get('form').appendChild(WLDiv);
													
														var location = Sys.UI.DomElement.getLocation(WLDiv);
														var windowLabelDiv = document.createElement('div');
														windowLabelDiv.setAttribute("id", "windowLabelDiv");
														$get('form').appendChild(windowLabelDiv);
														windowLabelDiv.style.position = 'absolute';
														windowLabelDiv.style.zindex = 9999;
														
														if(IE6Browser())
															windowLabelDiv.innerHTML +=String.format("{0}{1}", "<div style='position:absolute; left:"+String.format("{0}{1}", location.x - 75, "px")+"; top:" + String.format("{0}{1}", e.clientY - 12, "px")+"; border: #efefff 1px solid; WIDTH: 70px; Z-INDEX: 9999;BACKGROUND-COLOR: #366ab3;'><center><a id='" +wlObj.get_WindowLabel()+"' style='DISPLAY: inline; FONT-WEIGHT: bold;FONT-SIZE: 7pt; VERTICAL-ALIGN: baseline; COLOR: #ffffcc; FONT-STYLE: normal; FONT-FAMILY: verdana; bottom:17px; TEXT-ALIGN:center;'>"+ String.format("{0}{1}","W: ",imgObj.get_Window()) +"</a></center></div>",
																										   "<div style='position:absolute; left:"+String.format("{0}{1}", location.x + width +5, "px")+"; top:" + String.format("{0}{1}", e.clientY - 12, "px")+"; border: #efefff 1px solid; WIDTH: 70px; Z-INDEX: 9999;BACKGROUND-COLOR: #366ab3;'><center><a id='" +wlObj.get_LevelLabel()+"' style='DISPLAY: inline; FONT-WEIGHT: bold;FONT-SIZE: 7pt; VERTICAL-ALIGN: baseline; COLOR: #ffffcc; FONT-STYLE: normal; FONT-FAMILY: verdana; bottom:17px; TEXT-ALIGN:center;'>"+ String.format("{0}{1}","W: ",imgObj.get_Level()) +"</a></center></div>");
													
														else
															windowLabelDiv.innerHTML +=String.format("{0}{1}", "<div style='position:Fixed; left:"+String.format("{0}{1}", location.x - 75, "px")+"; top:" + String.format("{0}{1}", e.clientY - 12, "px")+"; border: #efefff 1px solid; WIDTH: 70px; Z-INDEX: 9999;BACKGROUND-COLOR: #366ab3;'><center><a id='" +wlObj.get_WindowLabel()+"' style='DISPLAY: inline; FONT-WEIGHT: bold;FONT-SIZE: 7pt; VERTICAL-ALIGN: baseline; COLOR: #ffffcc; FONT-STYLE: normal; FONT-FAMILY: verdana; bottom:17px; TEXT-ALIGN:center;'>"+ String.format("{0}{1}","W: ",imgObj.get_Window()) +"</a></center></div>",
																										   "<div style='position:Fixed; left:"+String.format("{0}{1}", location.x + width +5, "px")+"; top:" + String.format("{0}{1}", e.clientY - 12, "px")+"; border: #efefff 1px solid; WIDTH: 70px; Z-INDEX: 9999;BACKGROUND-COLOR: #366ab3;'><center><a id='" +wlObj.get_LevelLabel()+"' style='DISPLAY: inline; FONT-WEIGHT: bold;FONT-SIZE: 7pt; VERTICAL-ALIGN: baseline; COLOR: #ffffcc; FONT-STYLE: normal; FONT-FAMILY: verdana; bottom:17px; TEXT-ALIGN:center;'>"+ String.format("{0}{1}","W: ",imgObj.get_Level()) +"</a></center></div>");
													
														//trace("Ratio: " + resizeRatio);
														if(imgObj.get_ScalingArithmeticOperation() != 'Division' )
															wlObj.Rectangle(Math.round(x0 / resizeRatio), Math.round(y0 / resizeRatio), Math.round(height/ resizeRatio),  Math.round(width/ resizeRatio));
														else
															wlObj.Rectangle(Math.round(x0 * resizeRatio), Math.round(y0 * resizeRatio), Math.round(height* resizeRatio),  Math.round(width* resizeRatio));
														
														wlObj.GetNewWindowLevelImg();
														
													}	
													
													break;
												  }
			case Sys.UI.MouseButton.leftButton : {
														
													if(e.target.id !=""){
														var imageObj = e.target.id.indexOf('Prim') != -1 ? image : imageComp;
														var tool = imageObj.get_IsComparison() ? zoomComp : zoom;	//zoom is really the only other tool that toggles beside measure
														if(!tool.ZoomOn()){
															 if(e.target.id.startsWith('Img') && $get(e.target.id).tagName =="IMG"){
																var imageObj = e.target.id.indexOf('Prim') != -1 ? image : imageComp;
																imageObj.set_Position(parseInt(e.target.id.substring(e.target.id.length-1)));
																imageObj.set_ActiveImg(e.target.id);
																imageObj.set_ImgNum(parseInt(imageObj.get_SeriesImgs()[imageObj.get_CurrentArrayIndex() + imageObj.get_Position()]));
																imageObj.set_CurrentArrayIndex(imageObj.get_CurrentArrayIndex() + imageObj.get_Position());
																
																var focusObj = e.target.id.indexOf('Prim') != -1 ? mouse : mouseComp;
																var measureObj = e.target.id.indexOf('Prim') != -1 ? measure : measureComp;
																focusObj.set_StartLocationX(e.clientX);
																focusObj.set_StartLocationY(e.clientY);
														
																$addHandler(document,'mousemove', MouseMove); 
																$addHandler(document,'mouseup', MouseRelease);
																_activeSeriesScrolling = imageObj.get_IsComparison() ? 'Series2':'Series1';
																//$addHandler($get(e.target.id),'mouseout', MouseRelease);
																
																if(measureObj.MeasureOn()){
																	if(e.target.id.indexOf('Prim') == -1)
																		measureComp.DrawLine(e);
																	else
																		measure.DrawLine(e);
																}
																
															
																var domEventVar = new Sys.UI.DomEvent(e);
																domEventVar.preventDefault();
																domEventVar.stopPropagation();
																}	
																
															if(e.target.id == 'toolMenuZoomIcon' || e.target.id == 'toolMenuZoomIconComp'){
																var focusObj = e.target.id.indexOf('Comp') == -1 ? measure : measureComp;
																	//check to make sure no other tools is on before enabling this tool 
																	if(focusObj.MeasureOn()){
																		focusObj.MeasureModeOFF();
																		}
															}
														}
													}
												
													break;		
												 }
		case Sys.UI.MouseButton.middleButton : {
												alert('This feature is not available yet');
													break;
												}
		}
	}
	
	
	function MouseMove(e){
		
			var measureObj =_activeSeriesScrolling =='Series1' ? measure : measureComp;
			var wlObj = _activeSeriesScrolling =='Series1' ? windowLevel : windowLevelComp;
			
			 if(wlObj.get_On())
				  wlObj.WindowLevelCurrentImage(e);
			 
			 else{											
					
				 if(measureObj!=null && measureObj.get_On())
					measureObj.DrawLine(e);
					
				 else{
					Scrolling(e);
					//otherwise I am scrolling
	//				if(e.target.tagName != "IMG")
	//					$addHandler(form, 'mouseout', MouseExitBrowser);
					}
				}
				
			var domEventVar = new Sys.UI.DomEvent(e);
				domEventVar.preventDefault();
				domEventVar.stopPropagation();
		//}
		
		
	}
	
	//MouseRelease
	function MouseRelease(e){
		
			var measureObj = _activeSeriesScrolling =='Series1' ? measure : measureComp;
			var imageObj = _activeSeriesScrolling =='Series1' ? image : imageComp;
			
			
			if((new Sys.UI.DomEvent(e)).button == Sys.UI.MouseButton.rightButton){
					var wlObj = _activeSeriesScrolling =='Series1' ? windowLevel : windowLevelComp;
					wlObj.set_On(false);
					
					//clear the div
					$get('form').removeChild($get('wlDiv'));
					$get('form').removeChild($get('windowLabelDiv'));
					
				  //asynchronous ajax call that sets the new window / level values in the series session object
					SetWindowLevel(imageObj.get_Window(), imageObj.get_Level());
					
					
					//remove the cached images so that the new images will load into the cache with the new window level
					var cache = imageObj.get_IsComparison() ? imageCacheComp : imageCache;
					cache.RemoveCacheImgs();
					
					//reload images and thumbs at new window/level
					imageObj.set_ImgNum(imageObj.get_SeriesImgs()[imageObj.get_CurrentArrayIndex() - imageObj.get_Position()]);
					imageObj.SynchronizeThumbs();
				}
			else{
				if(!measureObj.MeasureOn()){
					imageObj.set_ImgNum(imageObj.get_SeriesImgs()[imageObj.get_CurrentArrayIndex() - imageObj.get_Position()]);
				
					//imageObj.LoadImages();
					//make sure the image exists in the series
				
						for(var i = 0; i<=imageObj.get_SeriesImgs().length - 1; i++)
						{
							if(imageObj.get_SeriesImgs()[i]  == imageObj.get_ImgNum()){
								imageObj.set_CurrentArrayIndex(i);
								break;
								}
						}
						var layout =_activeSeriesScrolling =='Series1' ? layOut : layOutComp;
						var totalPositions = layout.get_Rows() * layout.get_Cols();
						if(imageObj.get_SeriesImgs().length < totalPositions){
							//imageObj.set_ImgNum(imageObj.get_SeriesImgs()[0]);
							imageObj.set_CurrentArrayIndex(0);
						}
					imageObj.SynchronizeThumbs();
					}
				}
		
		$removeHandler(document,'mousemove', MouseMove); 
		$removeHandler(document,'mouseup', MouseRelease);
	}
	
	//Depeprecated - this is no longer needed since we only allow scrolling on the img
	function MouseExitBrowser(e){
		try{
			MouseRelease(e);
			}
		catch(ex){}
		finally{
			$removeHandler(form, 'mouseout', MouseExitBrowser);
		}
	}
	
	/** Event handler for mouse wheel event.
 */
	function Wheel(event){
			var delta = 0;
			//if (event.rawEvent.wheelDelta) /* For IE. */
			//		event = event.rawEvent.wheelDelta;
			if (event.rawEvent.wheelDelta) { /* IE/Opera. */
					delta = event.rawEvent.wheelDelta/120;
					/** In Opera 9, delta differs in sign as compared to IE.
					 */
					if (window.opera)
							delta = -delta;
			} else if (event.detail) { /** Mozilla case. */
					/** In Mozilla, sign of delta is different than in IE.
					 * Also, delta is multiple of 3.
					 */
					delta = -event.detail/3;
			}
			/** If delta is nonzero, handle it.
			 * Basically, delta is now positive if wheel was scrolled up,
			 * and negative, if wheel was scrolled down.
			 */
			if (delta)
					handle(delta);
			/** Prevent default actions caused by mouse wheel.
			 * That might be ugly, but we handle scrolls somehow
			 * anyway, so don't bother here..
			 */
			if (event.preventDefault)
					event.preventDefault();
		event.returnValue = false;
	}
	
	function handle(delta) {
		var i = 0
		for(; i <image.get_AllStudySeries().length; i++){
				if(image.get_AllStudySeries()[i] == image.get_SeriesNum())
				break;
			}
        if (delta < 0){
			if(i-1 < 0)
				image.set_SeriesNum(image.get_AllStudySeries()[image.get_AllStudySeries().length-1]);
			else
				image.set_SeriesNum(image.get_AllStudySeries()[i-1]);
			}
        else{
			if(i+1 >= image.get_AllStudySeries().length)
				image.set_SeriesNum(image.get_AllStudySeries()[0]);
			else
				image.set_SeriesNum(image.get_AllStudySeries()[i+1]);
			}
		//load the study and series thumbnail strips
		GetSeriesImageNum(image.get_SeriesNum());
}


	
/*****************************************************
* ToolBar Class Object
*
* Used to reposition and hide and show the appropriate toolbars, this includes while in split series 
******************************************************/

function ToolBar(value){
	//--------internal variables--------
	var _isComparison = value;
	
	//--------properties--------
	this.get_IsComparison = function(){
		return _isComparison;
	}
	this.set_IsComparison = function(value){
		_isComparison = value;
	}


	//--------methods--------

	this.LoadToolbars = function(){
		
		$get('toolMenuComp').style.display = 'none';
		$get('toolMenu').style.display = '';
		
		if(IE6Browser()){
			if($get('ImgPrimDiv')!=null)
			$get('dock').style.width = $get('Series1Div').clientWidth +"px";
			$get('toolMenu').style.width = $get('Series1Div').clientWidth+"px";
        
			if(this.get_IsComparison()){
				$get('dockComp').style.width  = $get('Series2Div').clientWidth+"px";
				$get('toolMenuComp').style.width  = $get('Series2Div').clientWidth+"px";
				$get('toolMenuComp').style.display = '';
				$get('toolMenuComp').style.left = $get('Series1Div').clientWidth;
			}
		}
		
		else{
			if($get('ImgPrimDiv')!=null)
				$get('toolMenu').style.left = ($get('ImgPrimDiv').clientWidth/2) - ($get('toolMenu').offsetWidth/2) + leftMargin+'px';//(((g_resizer.offsetWidth - leftMargin - rightMargin)/2) - ($get('toolMenu').offsetWidth/2))+'px';
        
			if(this.get_IsComparison()){
				$get('toolMenuComp').style.display = '';
				$get('toolMenuComp').style.left = ($get('ImgPrimDiv').clientWidth +leftMargin) + ($get('ImgCompDiv').clientWidth/2) - ($get('toolMenu').offsetWidth/2) +'px';
			}
		}
			
			//pertains to bug#6260, I will leave commented out until the bug becomes assigned to me
			//add a close button to series2 in the upper left hand corner
//			var image = document.createElement("img");
//			image.setAttribute("id", 'CloseSeries2');
//			image.setAttribute('src', 'Images/Close.JPG');
//			$get('Series2Div').appendChild(image);
//			var closeBtn = $get('CloseSeries2');
//			closeBtn.style.position = 'absolute';
//			closeBtn.style.float = 'right';
//			closeBtn.style.right ='0px';
//			closeBtn.style.top = '0px';
	}

}

/***********************************************************************************************************************
* General functions that don't really belong to a single object -
************************************************************************************************************************/
/*****************************************************
* Scrolling -- function called when the mousedown event 
* handler is attahed and no tools are active.  Allows 
* image scrolling up and down.
******************************************************/
function Scrolling(e){
	var domEventVar = new Sys.UI.DomEvent(e);
	if(domEventVar.button == Sys.UI.MouseButton.leftButton ){
	
			var imageObj = _activeSeriesScrolling =='Series1' ? image : imageComp;
			var mouseObj = _activeSeriesScrolling =='Series1' ? mouse : mouseComp;
			
				//determine the direction of the image scrolling
				if((e.clientY - mouseObj.get_StartLocationY()) > 0 && (e.clientY - mouseObj.get_StartLocationY()) > 4){
				//make sure that you are not at the end of series
					if(imageObj.get_CurrentArrayIndex() + 1 <= imageObj.get_SeriesImgs().length){
						//  get the div selected to determine the position of the image and which 
						imageObj.set_ImgNum(parseInt(imageObj.get_SeriesImgs()[imageObj.get_CurrentArrayIndex() + 1]));
						imageObj.set_ScrollingDir("increasing");
						imageObj.ScrollImage();
					}
					mouseObj.set_StartLocationY(e.clientY);
				}
				
				else if((mouseObj.get_StartLocationY() - e.clientY) > 0 &&(mouseObj.get_StartLocationY() - e.clientY) > 4){
				//make sure that you are not at the begining of the series
					if(imageObj.get_CurrentArrayIndex()-1 >= 0){
						imageObj.set_ImgNum(parseInt(imageObj.get_SeriesImgs()[imageObj.get_CurrentArrayIndex() - 1]));
						imageObj.set_ScrollingDir("decreasing");
						imageObj.ScrollImage();
					 }
					mouseObj.set_StartLocationY(e.clientY);
				}
				imageObj.set_ScrollingDir("undefined");
			}
}
//this function is automatically called by ajax, therefore no need to ever call it
function pageLoad(){
	
	try{
		$removeHandler(document, "mousedown", MouseDown);
		}
	catch(ex){}
	$addHandler(document, "mousedown", MouseDown);
	
}

function ViewStudy(studyUID){
		$get('ViewStudyFirstSeries').value = "true";
		GetFirstSeriesInStudy(studyUID);
		var t = setTimeout($get('ViewStudyFirstSeries').value = "false", 3000);
		
		if($find('ModalPopUp') != null){
		    var behavior = $find('ModalPopUp'); //unmodal the study browser
		    behavior.hide();
		}
	
}
/*****************************************************
* AddPrimaryElement -- this is the first function called
* when a series is selected from the gridview.  Attaches
* a mouse down event to the document and creates the 'ImgPrimDiv'
******************************************************/
function AddPrimaryElement(){
	//check session on the interval for a authentication principle
	setInterval('HasSessionExpired()',checkSessionInterval);
	
	try{
		$removeHandler(document, "mousedown", MouseDown);
	}
	catch(ex){}
	
	image.set_StringImageCompressed(GetLocalizedStrings());
	
	//this will be relooked at in the future
	//	try{
	//		$removeHandler(document, "wheel", Wheel);
	//	}
	//	catch(ex){}
	$addHandler(document, "mousedown", MouseDown);
	//$addHandler(document, "mousewheel", Wheel);
	if(!comparisonMode){
		imageCache.RemoveCacheImgs();
		
		//before I create new 'ImgPrimDiv' I need to destroy the first one
		if($get('ImgPrimDiv')!=null)
		$get('Series1Div').removeChild($get('ImgPrimDiv'));
		CreateDiv('Series1Div', 'ImgPrimDiv','div','DivViewerFull');

		if(typeof(g_resizer) == "undefined")
			g_resizer = document.documentElement;
			
		$get('ImgPrimDiv').style.width = (g_resizer.clientWidth - leftMargin - rightMargin - divPadding)+'px';
		$get('ImgPrimDiv').style.height = (g_resizer.clientHeight - (2*topMargin))+'px';
		
		toolbar.LoadToolbars();
	}
	else {
			imageCacheComp.RemoveCacheImgs();		//remove any previous Cache Imgs
			$get('ImgCompDiv').style.height = $get('ImgPrimDiv').style.height;
			$get('ImgCompDiv').style.top = String.format("{0}{1}", topMargin,"px");
		}
		
	//make sure the ModalPopUp is available 
	if($find('ModalPopUp') != null){
		var behavior = $find('ModalPopUp'); //unmodal the study browser
		behavior.hide();
	}
	
}


function init(){
	g_resizer = document.documentElement;
	window.g_prevSize = { h: g_resizer.clientHeight, w: g_resizer.clientWidth };
 
	if (window == document.body)
		resizeTimerID = setInterval(resize, 100); 
	else
		window.onresize = resize;
}

/*****************************************************
* Resize -- attached to the window event that 
* resizes the images when the browser window is resized.
******************************************************/
function resize(){ 
	try{
		var viewportSize = getViewportSize();
		
		var currentSize = { h: viewportSize[0], w: viewportSize[1] };
		if (currentSize.h != g_prevSize.h || currentSize.w != g_prevSize.w){
				g_prevSize = currentSize;
				 ResizeWindow();
			}
		}
		catch(ex){}
}
function getViewportSize()
{
	var size = [0, 0];
	if (typeof window.innerWidth != 'undefined')
		size = [window.innerHeight, window.innerWidth];
	else if (typeof document.documentElement.offsetHeight != 'undefined' && document.documentElement.offsetHeight != 0)
		size = [document.documentElement.offsetHeight, document.documentElement.offsetWidth];
	else if (typeof document.documentElement != 'undefined' && typeof document.documentElement.clientWidth != 'undefined' && document.documentElement.clientWidth != 0)
		size = [document.documentElement.clientHeight, document.documentElement.clientWidth];
    else
		size = [document.getElementsByTagName('myBody')[0].clientHeight, document.getElementsByTagName('myBody')[0].clientWidth];
	return size;
}
function ResizeWindow(){
	//check to see if the flag allows resize in this mode
	if(resizeWindow){
	try{
		
		if($get('ImgPrimDiv') != null){
			$get('ImgPrimDiv').style.height = (g_resizer.clientHeight - topMargin) +'px';
			$get('ImgPrimDiv').style.width = (g_resizer.clientWidth - leftMargin - rightMargin)+'px';
			image.SetUpImg();
			}
		if($get('ImgCompDiv') != null){
			$get('ImgCompDiv').style.height = (g_resizer.clientHeight - topMargin) +'px';
			$get('ImgCompDiv').style.width = (g_resizer.clientWidth - leftMargin - rightMargin)+'px';
			//imageComp.SetUpImg();
			}
		
		if($get('PrimaryThumb') != null){
			$get('PrimaryThumb').style.top = (((g_resizer.clientHeight -topMargin)-(image.get_ThumbNailCount()*70))/2) + 'px';
		}
		if($get('CompThumb') != null){
			$get('CompThumb').style.top = (((g_resizer.clientHeight -topMargin)-(imageComp.get_ThumbNailCount()*70))/2) + 'px';
		}
		if($get('StudySeriesThumbStrip') != null){
			$get('StudySeriesThumbStrip').style.top = (((g_resizer.clientHeight-topMargin) -(image.get_AllStudySeries().length *70))/2) > 0? (((g_resizer.clientHeight-topMargin) -(image.get_AllStudySeries().length *65))/2)+ 'px' : 0+'px';
		}
		
		//make sure the grid headers are showin.g
		if($get('dgPatients') != null)
		    $get('dgPatients').childNodes[0].style.display = "block";
			
		if($get('dgSeries') != null)
		    $get('dgSeries').childNodes[0].style.display = "block";
		
			
		//reposition the menu 
		if($get('toolMenu').style.display == '')
			toolbar.LoadToolbars();
		
		SaveScrollLocation();
			
		}
		catch(ex){/*this is a valid exception because if the click on the close div in the ris, it will trigger a resize event, so I just do not want the code above to error*/}
	}
}



/*****************************************************
* CreateDiv -- Dynamically creates a div and/or image
* and appends it to the parent object.  Sometimes a 
* cssClass is passed in, if not pass null
******************************************************/
function CreateDiv(refPoint, divIdName, type, cssClass){
	var refDiv = refPoint;
	refDiv = (Object.getTypeName(refPoint) == 'String') ? $get(refPoint) : refDiv.get_element(); //this is because sometimes I pass a object and othertimes I pass and string object name
	var newdiv = document.createElement(type);
	newdiv.setAttribute('id',divIdName);
	newdiv.setAttribute('runat','server');
	//if(type == 'img')									
	//	newdiv.setAttribute('onerror', imgErrorHandler);	//if is an image set the imgErrorHandler
	refDiv.appendChild(newdiv);
	
	if(cssClass != null)
	$get(newdiv.id).className = cssClass;	//must set after the div has been appended
	return newdiv;
	}

/*****************************************************
* AddImageElement
******************************************************/
function AddImageElement(width, height, position, object, layout){
	var parentDiv; 
	var parentDivName;
	var childImgName;
	
	if(!object.get_IsComparison()){
		parentDiv = getObject('ImgPrimDiv',Sys.UI.Control);
		parentDivName = 'ImgPrimDiv';
		childImgName = 'ImgPrim';
		}
	
	else{
			parentDiv = getObject('ImgCompDiv',Sys.UI.Control);
			parentDivName = 'ImgCompDiv';
			childImgName = 'ImgComp';
		}
		
	if($get(parentDivName + position) == null){
		CreateDiv(parentDiv._element.id, parentDivName + position,'div','ImgPosition0');
		
	 
		var divElement = getObject(parentDivName + position,Sys.UI.Control);
		divElement.addCssClass('ImgPosition0');
		
		var location = null;
		
		switch(position){

			case 1 : {	
						Sys.UI.DomElement.setLocation(divElement._element, parseInt(width), parseInt(0)); 
						break;}
			case 2 : {	
						Sys.UI.DomElement.setLocation(divElement._element, parseInt(0), parseInt(height));
						//divElement._element.style.left = '';
						//divElement._element.style.right = parseInt(width);
						
						break;}
			case 3 : {	
						Sys.UI.DomElement.setLocation(divElement._element, parseInt(width), parseInt(height)); 
						break;}
			default : { 
						if(layout > 1){
						//divElement._element.style.left = '';
						//divElement._element.style.right = parseInt(width);
						}
							
						break;
					  }
			}
			
		CreateDiv(parentDivName + position, childImgName + position, 'img', null); 
	}
	return childImgName + position;
}

/*****************************************************
* RemoveImgElement -- removes all images from the parent
* object( usually this is a div)
******************************************************/
function RemoveImgElement(parentElement) {
  try{
	  var len = parentElement.childNodes.length;
	  for(var i=len; i>0; i--)
	  {
		parentElement.removeChild(parentElement.childNodes[i-1]);
	  }
  }
  catch(ex){}
}


/*****************************************************
* getObject -- general function I created to create an
* abstraction object or if one exist get reference to it.
******************************************************/
function getObject(domElement, classType){
	try{
	return ($get(domElement).control != null) ? $get(domElement).control : new classType($get(domElement));
	}
	catch(ex){};
}

/*****************************************************
* imgErrorHandler -- attached to the onerror event of
* all images, this will continue to be called until 
* image reloads or the stack overflows - TODO: figure out a way to prevent the stack from overflowing
******************************************************/
 function imgErrorHandler() {alert(this.src);
			try{
			//setTimeout(imgErrorHandler, 5000)
				var time = new Date();
				var alternativeSrc = this.src +'&Error=' + time.getMilliseconds;
				//var replacementstring = alternativeSrc.substring(alternativeSrc.indexOf("Loading", alternativeSrc.length));
				var img = getObject(this.id,Sys.Preview.UI.Image);
				//img.set_imageURL(alternativeSrc.replace(replacementstring,'undefined'));
				img.addCssClass('imgErrorHandler');
				this.src = alternativeSrc;
				return true;
			   }
				catch(ex){}
			}

/*****************************************************
* imgLoadedHandler() -- attached to the onload event of
* all viewer images, when the image has loaded it will remove
* the background image (loading.gif) so that unnecessary 
* cpu cycles are not being used
******************************************************/
function imgLoadedHandler() {
	this.style.backgroundImage = "url(Images/LoadingPic.gif)";
	this.style.backgroundPosition = '';
	this.style.backgroundRepeat = '';
	this.style.cssText='';
	var itemExists = false;
	
	
		if(this.id !=null && $get(this.id)!=null){
			var comp = this.id.indexOf('Prim') == -1 ? true : false;
			var imageObj = comp ? imageComp : image;
			
			var width = $get(this.id).width;
			var height = $get(this.id).height;
			//Here I need to check the image size and compare it to any previous image sizes
			if(imageObj.get_ImgWidth() != width || imageObj.get_ImgHeight() != height){
				imageObj.set_ImgWidth(width);
				imageObj.set_ImgHeight(height);
				GetImageScaleData(imageObj.get_IsComparison());
			}
			
			
			
			if(Array.contains(imageObj.get_PreLoadedImgNums(), this.src.substring(this.src.indexOf("&imgNum=")+8,this.src.indexOf("&Width=")))){
				Array.remove(imageObj.get_PreLoadedImgNums(), this.src.substring(this.src.indexOf("&imgNum=")+8,this.src.indexOf("&Width=")))
				
				if(imageObj.get_PreLoadedImgNums().length == 0){
					if(imageObj.get_CompressionRatios().length != 0)
						FetchUpdateCompressionList(imageObj.get_IsComparison());
				}
			}
		}
	}
	
	
function ScrollingOnComplete(){
	//trace("Start ScrollingOnComplete " + new Date().getSeconds() +":" + new Date().getMilliseconds());
	this.style.onload = "";
	var cache = this.id.indexOf('Prim') == -1 ? imageCacheComp : imageCache;
	var imageObj = cache.get_IsComparison() ? imageComp : image;
	var imgNum = this.src.substring(this.src.indexOf("&imgNum=")+8,this.src.indexOf("&Width="));
	
	
	if(imageObj.get_ScrollingDir() == "increasing"){//trace("Start " + new Date().getSeconds() +":" + new Date().getMilliseconds());
		if(!Array.contains(cache.get_CacheArray(), parseInt(imgNum) + (cache.get_CacheSize()/2)))
			//trace("Next " + new Date().getSeconds() +":" + new Date().getMilliseconds());
			cache.AddCacheImg(parseInt(imgNum) + (cache.get_CacheSize()/2));
			//trace("End " + new Date().getSeconds() +":" + new Date().getMilliseconds());
	}
	else{
		if(!Array.contains(cache.get_CacheArray(), parseInt(imgNum) - (cache.get_CacheSize()/2)))
			cache.AddCacheImg(parseInt(imgNum) - (cache.get_CacheSize()/2));
	}
	//trace("End ScrollingOnComplete" + new Date().getSeconds() +":" + new Date().getMilliseconds());
}
/*****************************************************
* WLImgLoadComplete() -- attached to the onload event of
* all dynamic window/level images, when the image has loaded it will 
* load the image quickly and not an empty image
******************************************************/
function WLImgLoadComplete(){					
	var img = getObject('WindowLevelingImg',Sys.Preview.UI.Image);
	/* It can be null if the server is busy and the user has already released the mousebutton*/
	if(img !=null)
		img.set_imageURL(this.href);
}

/*****************************************************
* ShowHelpTopic - used by the HelpOptions.ascx to hide 
* and show the selected topic 
******************************************************/
function ShowHelpTopic(value){
	$get(currentHelpTopic).style.display ='none';
	$get(value).style.display ='';
	currentHelpTopic = value;
}

function ShowHelpMenu(show){
	var behavior = $object('PopEx');
	if(show)
		behavior._popupBehavior.show();
	else
		behavior._popupBehavior.hide();
}

/*****************************************************
* DisableToolMenu -- disables all features on the 
* toolMenu except the current instance
******************************************************/
function DisableToolMenu(instance){
	if(!instance.get_IsComparison()){
		layOut.IconDisable();
		zoom.IconDisable();
		comparison.IconDisable();
		windowLevel.IconDisable();
		studyBrowser.IconDisable();
		measure.IconDisable();
	}
	else{
		layOutComp.IconDisable();
		zoomComp.IconDisable();
		windowLevelComp.IconDisable();
		studyBrowserComp.IconDisable();
		measureComp.IconDisable();
	}
	//enable the one you want to use
	instance.IconEnable();	
}

/*****************************************************
* EnableToolMenu -- enables all features on the toolMenu
******************************************************/
function EnableToolMenu(instance){
	//enable the one you want to use
	if(!instance.get_IsComparison()){
		layOut.IconEnable();
		zoom.IconEnable();
		comparison.IconEnable();
		windowLevel.IconEnable();
		studyBrowser.IconEnable();
		measure.IconEnable();
	}
	else{
		layOutComp.IconEnable();
		zoomComp.IconEnable();
		windowLevelComp.IconEnable();
		studyBrowserComp.IconEnable();
		measureComp.IconEnable();
	}	
}

/*****************************************************
* PreviousPage -general function that is called when the
* the users selects the up arrow on the the thumbnail 
* images within a series
******************************************************/
function PreviousPage(event){
	var imageObj;
	var domElement;
	if(event != null){
		 imageObj = event.id.indexOf('Comp')== -1 ? image : imageComp;
			e = event.id;
		 }
		imageObj.PreviousPage(e);
}

/*****************************************************
* NextPage - general function that is called when the
* the users selects the down arrow on the the thumbnail 
* images within a series
******************************************************/
function NextPage(event){
	var imageObj;
	var domElement;
	if(event!= null){
		 imageObj = event.id.indexOf('Comp')== -1 ? image : imageComp;
			 e = event.id;
		 }
		imageObj.NextPage(e);
}

/*****************************************************
* Selected - Used when the user selects a thumbnail 
* image within the series (left sided thumbnails)
******************************************************/
function Selected(element){
	var imgData = element.lang;		//I have stuck the series and image info in the long desc, it is never used anyway.
	var imgObj = element.id.indexOf('Comp') != -1 ? imageComp : image;
	//imgObj.SetUpImg(imgData);					//The real reason is that I can not add a instance of an object to a handler so I have to call a global function that has no parameters
	
	var pagingObj = element.id.indexOf('Comp') != -1 ? imagePagingComp : imagePaging;
	pagingObj.ToggleSelectedPage(element);
	
	/*determine if I am at the start page or end page of the thumbnail viewer
	 *because if I am I want to refactor the thumbnails so the viewer and thumbs are the same
	 */
		if(pagingObj.IsLastThumbnail() || pagingObj.IsFirstThumbnail()){
			var imgNum = imgObj.ParseImgNum(imgData);
			imageObj.set_ImgNum(imgNum);
			imageObj.LoadImages();
			imageObj.SynchronizeThumbs();
		}
		else
			imgObj.SetUpImg(imgData);
					
	
}

/*****************************************************
* SwitchSeries - changes the current series to the 
* newly selected series 
******************************************************/
function SwitchSeries(element){
		imageCache.RemoveCacheImgs();
		image.set_SeriesNum(element.longDesc.substr(element.longDesc.lastIndexOf('/')+1));
		GetSeriesImageNum(image.get_SeriesNum());
		image.Reset();
	}
	
/*****************************************************
* ToggleIcon - used to toggle the on / off of an icon 
* simply adds a border to the element
******************************************************/
function ToggleIcon(e, recursiveCall){;
	
		var firingElement = e!=null ? e.id : event.target.id;
		
		if(firingElement == "toolMenuComparisonIconComp" || firingElement == "toolMenuComparisonIcon"){
				if($get("toolMenuComparisonIcon").style.border != "")
					$get("toolMenuComparisonIcon").style.border = "";
				else
					$get("toolMenuComparisonIcon").style.border = "#ff6600 thin solid";
					
				if($get("toolMenuComparisonIconComp").style.border != "")
					$get("toolMenuComparisonIconComp").style.border = "";
				else
					$get("toolMenuComparisonIconComp").style.border = "#ff6600 thin solid";
		}
		
		else{
				if($get(firingElement).style.border != "")
					$get(firingElement).style.border = "";
				else
					$get(firingElement).style.border = "#ff6600 thin solid";
			}
}

/*****************************************************
* SaveScrollLocation - sets the scroll postion in the 
* gridview for both study and series, so that after a 
* postback occurs on the page the previous scroll 
* position on the grid is resumed
******************************************************/
 function SaveScrollLocation(){
            var hiddenField = $get('hdScrollPos');
            var gridDiv = $get('studyGridDiv');
            if(gridDiv != null)
                hiddenField.value = gridDiv.scrollTop;	                   
        }

/*****************************************************
* Close - ends the split screen mode and returns to the single series1 
******************************************************/
function Close(e){
			try{
			//remove any objects that may have been set up in anticipation of the splitscreen that never happened
			comparison.ComparisonMode();
			
			//show the toolbar option as inactive
			$get('toolMenuComparisonIcon').style.border = "";
			}
			catch(ex){
				
			}
	} 
	


function ChangeWindowLevelByPreset(object, itemSelected){
	if(itemSelected.parentNode.parentNode.id.indexOf('Comp') != -1)
		windowLevelComp.ChangeWindowLevelByPreset(itemSelected.id);
	else
		windowLevel.ChangeWindowLevelByPreset(itemSelected.id);
	
}
function trace( msg ){
  if( typeof( jsTrace ) != 'undefined' ){
    jsTrace.send( msg );
  }
}

function IE6Browser()
{	// IE 7, mozilla, safari, opera 9
	if (typeof document.body.style.maxHeight != "undefined") 
		return false;
	// IE6, older browsers
	else 
		return true;
}



//function ImagePreloader(images, call_back){

//   // store the call-back
//   this.callback = call_back;

//   // initialize internal state.
//   this.nLoaded = 0;
//   this.nProcessed = 0;
//   this.aImages = new Array;

//   // record the number of images.
//   this.nImages = images.length;
//   
//   // for each image, call preload()
//   for ( var i = 0; i < images.length; i++ ) 
//      this.preload(images[i]);
//}

//ImagePreloader.prototype.preload = function(image){
//   // create new Image object and add to array
//   var oImage = new Image;
//   this.aImages.push(oImage);
// 
//   // set up event handlers for the Image object
//   oImage.onload = ImagePreloader.prototype.onload;
//   oImage.onerror = ImagePreloader.prototype.onerror;
//   oImage.onabort = ImagePreloader.prototype.onabort;

//   // assign pointer back to this.
//   oImage.oImagePreloader = this;
//   oImage.bLoaded = false;

//   // assign the .src property of the Image object
//   oImage.src = image;
//}

//ImagePreloader.prototype.onComplete = function(){
//   this.nProcessed++;
//   if ( this.nProcessed == this.nImages )
//   {
//	
//      this.callback(this.aImages, this.nLoaded);
//   }
//}

//ImagePreloader.prototype.onload = function(){
//   this.bLoaded = true;
//   this.oImagePreloader.nLoaded++;
//   this.oImagePreloader.onComplete();
//}

//ImagePreloader.prototype.onerror = function(){
//   this.bError = true;
//   this.oImagePreloader.onComplete();
//}

//ImagePreloader.prototype.onabort = function(){
//   this.bAbort = true;
//   this.oImagePreloader.onComplete();
//}





