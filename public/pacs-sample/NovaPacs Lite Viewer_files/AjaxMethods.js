// JScript File
function GetFirstSeriesInStudy(studyUID){
	var comp = $get('hdSeries').value == "Series2" ? true : false;
	PageMethods.GetFirstSeries(studyUID, GetFirstSeriesInStudy_CallBack);
}

function GetFirstSeriesInStudy_CallBack(seriesUID){
	var comp = $get('hdSeries').value == "Series2" ? true : false;
	var imageObj = comp ? imageComp : image;
	imageObj.set_SeriesNum(seriesUID);
	GetSeriesImageNum(seriesUID);
}
 /****************************************************************************************************
* used  to get all the series within a study
*
******************************************************************************************************/
function GetAllSeriesForStudy(){
	var comp = $get('hdSeries').value == "Series2" ? true : false;
	PageMethods.GetAllSeries(comp, GetAllSeriesForStudy_CallBack);
}

function GetAllSeriesForStudy_CallBack(result){
	var comp = $get('hdSeries').value == "Series2" ? true : false;
	if(result.AllSeries == null || result.AllSeries.length == '0' ){
		//GetAllSeriesForStudy();
	}
	else
	{	
		var obj = comp ? imageComp : image;
		obj.ClearAllStudySeries();
		for(var i=0; i < result.AllSeries.length; i++)
			{
				obj.AddStudySeries(result.AllSeries[i])
				obj.AddSeriesMiddleImg(result.AllMiddleImages[i]);
				obj.AddSeriesDesc(result.AllSeriesDesc[i]);
				
			}
		//load the thumbnail strip of series images starting with the middle image
			obj.LoadSeriesImages();
		if(!comp & $get('hdSplitScreen').value != "true")
			obj.LoadStudySeries();
			
	}
}

 /****************************************************************************************************
* used  to get the series count for the Primary Image
*
******************************************************************************************************/
function GetSeriesImageNum(seriesUID){
		
		if(seriesUID !=null){
			var comp  = $get('hdSeries').value == "Series2" ? true : false;
			var obj = comp ? imageComp : image;
			
			try{ g_resizer.clientWidth }
			catch(ex){ var g_resizer = document.documentElement; }
			
			var width = (g_resizer.clientWidth - leftMargin - rightMargin - divPadding);
			if(!comp)
				var height = g_resizer.clientHeight - (2*topMargin);
			else 
				var height  = g_resizer.clientHeight - topMargin;
		
			
		//clear out the array that holds the series images
		obj.ClearSeries();
		//since I am getting a new series I need to clear out some of the old series garbage
				
		var pagingObj = comp ? imagePagingComp : imagePaging;
		pagingObj.set_CurrentPage(null);
				
		PageMethods.GetSeriesImageNums(comp,seriesUID, height, width, GetSeriesImageNum_CallBack);
		
		}
		
}

function GetSeriesImageNum_CallBack(result){
	var comp = $get('hdSeries').value == "Series2" ? true : false;
	
	if(result!=null){	//determine which type of image & layout objects 
			var obj = comp ? imageComp : image;
			var layOutObj = comp ? layOutComp : layOut;
			var reportObj = comp ? reportComp : report;
			var windowLevelObj = comp ? windowLevelComp : windowLevel;
			
			obj.AddSeriesImg(result.ImageNumbers);
			if(obj.get_StudyUID() != result.StudyUID)
				obj.set_StudyUID(result.StudyUID);
				
			
			switch(result.Modality.toUpperCase()){
				case 'DX' : { layOutObj.set_Rows(1); layOutObj.set_Cols(1); break;}
				case 'CR' : { layOutObj.set_Rows(1); layOutObj.set_Cols(1); break;}
	//					case 2 : { layOutObj.set_Rows(1); layOutObj.set_Cols(2); break;}
	//					case 3 : { layOutObj.set_Rows(1); layOutObj.set_Cols(3); break;}
	//					case 4 : { layOutObj.set_Rows(2); layOutObj.set_Cols(2); break;}
	//					case 5 : { layOutObj.set_Rows(2); layOutObj.set_Cols(3); break;}
	//					case 6 : { layOutObj.set_Rows(2); layOutObj.set_Cols(3); break;}
				default : { layOutObj.set_Rows(2); layOutObj.set_Cols(3); break;}
						
			}
			
			
			
			//set up the values of the following properties
			obj.set_SeriesNum(result.SeriesUID);
			obj.set_MiddleImage(null);
			obj.set_CurrentArrayIndex(null)
			obj.set_VoiceAnnotation(result.VoiceAnnotations);
			obj.set_ThumbNailCount(obj.get_DefaultThumbNailCount());
			obj.set_FullName(result.LastName + ', ' + result.FirstName); 
			obj.set_PatientID(result.PatientID);
			if(result.DOB != null)
				obj.set_DOB(result.DOB.format("d"));
			obj.set_Accession(result.AccessionNumber);
			obj.set_PatientGroup(result.PatientGroup);
			obj.set_StudyDesc(result.StudyDesc);
			//obj.set_SeriesDesc(result.SeriesDesc);
			obj.set_Modality(result.Modality);
			reportObj.set_hasReport(result.HasReport);
			
			windowLevelObj.set_DefaultWindow(result.SeriesWindow);
			windowLevelObj.set_DefaultLevel(result.SeriesLevel);
			obj.set_Window(result.SeriesWindow);
			obj.set_Level(result.SeriesLevel);
			obj.set_DicomImgHeight(result.ImageHeight);
			obj.set_DicomImgWidth(result.ImageWidth);
			
				
			if(result.PixelSpacingX != 0)
				obj.set_PixelSpacingX(result.PixelSpacingX);
			if(result.PixelSpacingY != 0)
				obj.set_PixelSpacingY(result.PixelSpacingY);
			//if(result.Scaling != 0)
			//	obj.set_ResizeRatio(result.Scaling);
			//obj.set_MultiFrame(result.MultiFrame);
			//obj.set_ScalingArithmeticOperation(result.ScalingArithmeticOperation);
			
			//if it is an us do not show the labels
			if(result.Modality == "US")
				obj.set_ShowLabels(false);
			else
				obj.set_ShowLabels(true);
				
			currentTime = new Date();
			obj.set_TimeStamp(currentTime);
			
			
			if(result.LossyCompressed != null )
				obj.set_CompressionRatios(result.LossyCompressed);
			
			
			//load the thumbnail strip of series images starting with the middle image
			//obj.LoadSeriesImages();
			
			//Make another ajax call to get the study series thumbnails and then load it with images
			GetAllSeriesForStudy();
			
	 }
}
///****************************************************************************************************
//* used  to get the series count for the Comparision Image
//*
//******************************************************************************************************/
//TODO: look at if one of these can go away
function GetImageScaleData(comparison, id){
   //PageMethods.GetSeriesLiteObject(comp, GetImageScale_CallBack);
   var imageObj = comparison ? imageComp : image;
   PageMethods.GetImageScale(comparison, imageObj.get_SeriesNum(), imageObj.get_ImgNum(), imageObj.get_ImgHeight(), imageObj.get_ImgWidth(), GetImageScale_CallBack)
}

function GetImageScale_CallBack(result){
	var obj = result.Series == "Series2" ? imageComp : image;
	if(result.PixelSpacingX != null)
		obj.set_PixelSpacingX(result.PixelSpacingX);
	if(result.PixelSpacingY != null)
		obj.set_PixelSpacingY(result.PixelSpacingY);
	obj.set_Window(result.SeriesWindow);
	obj.set_Level(result.SeriesLevel);

	if(result.Scaling != 0)
		obj.set_ResizeRatio(result.Scaling);
	obj.set_MultiFrame(result.MultiFrame);
	obj.set_ScalingArithmeticOperation(result.ScalingArithmeticOperation);
	
	
	if(result.LossyCompressed != null )
		obj.set_CompressionRatios(result.LossyCompressed);
	
}

function FetchUpdateCompressionList(comp){
	PageMethods.GetSeriesLiteObject(comp, FetchUpdateCompressionList_CallBack);
}

function FetchUpdateCompressionList_CallBack(result){
	if(result.LossyCompressed != null){
		var objImg = result.Series == "Series2" ? imageComp : image;
		objImg.set_CompressionRatios(result.LossyCompressed);
		objImg.UpdateLabels();
	}
	return;
}


/****************************************************************************************************
* used to populate the dropdown that contains window level presets for that specific modality
*
******************************************************************************************************/
function GetWindowLevelPresets(modality, comp)
{ 
	if(reloadWinLevelOptions){
		if(comp)	//comp = true means that I want comparison modalities
			$get('hdSeries').value = "Series2";
	   PageMethods.GetModalityWindowLevelOptions(modality, comp,GetModalityWindowLevelOptions_CallBack);
	}
	reloadWinLevelOptions = true;
}

 
function GetModalityWindowLevelOptions_CallBack(result){
	var divRef = $get('windowLevelDiv');
	var winObject = windowLevel;
	
	if($get('hdSeries').value == "Series2"){
		divRef = $get('windowLevelDivComp');
		winObject = windowLevelComp;
		}
		
	
	winObject.namePresets = result.Name;
	winObject.windowPresets = result.Window;
	winObject.levelPresets = result.Level;
	
	if(divRef.hasChildNodes()){	
		if($get(divRef.id+'table')!= null)
			divRef.removeChild($get(divRef.id+'table'));	
	}
	
	if(result.Name != null)
	{
	
	//add the default Preset
	winObject.namePresets[winObject.namePresets.length] = result.Default;
	winObject.windowPresets[winObject.windowPresets.length] = 0;
	winObject.levelPresets[winObject.levelPresets.length] = 0;
	
	
		var table = document.createElement("table");
		var body = document.createElement("tbody");
		table.setAttribute("id", divRef.id+'table');
			
				for(var i = 0; i <winObject.namePresets.length; i++){	
					var tr = document.createElement("tr");
					tr.id = i;
						//tr.setAttribute("id", winObject+'tr'+i);
						$addHandler(tr, "click", function(){ChangeWindowLevelByPreset($get('hdSeries').value, this)});
						$addHandler(tr, "mouseover", function(){this.style.cursor='hand'; this.style.textDecoration='underline'; this.style.color='#ff6600';});
						$addHandler(tr, "mouseout", function(){this.style.textDecoration='none';this.style.color='black';});
			
						var td1 = document.createElement("td");	
						var image = document.createElement("img");
						image.setAttribute("id",winObject.namePresets[i]+$get('hdSeries').value+'img');
						image.setAttribute('src', 'Images/menu_item_notselected.jpg');
						td1.appendChild(image);
						tr.appendChild(td1);
						var td2 = document.createElement("td");
						var text = document.createTextNode(winObject.namePresets[i]);
						td2.appendChild(text);
						tr.appendChild(td2);
					//add the row to the table
					body.appendChild(tr);
				}
		
			
		// appends <table> to the parent object
		table.appendChild(body);
		divRef.appendChild(table);
		
		$get(winObject.namePresets[winObject.namePresets.length-1]+$get('hdSeries').value+'img').src="Images/menu_item_selected.bmp";
		
	}
}

function SetWindowLevel(windowValue, levelValue){
	PageMethods.SetWindowLevelSessionValues(windowValue, levelValue, SetWindowLevel_CallBack);
}

function SetWindowLevel_CallBack(result){}


function GetWindowLevel(comp){
	PageMethods.GetSeriesLiteObject(comp, GetWindowLevel_CallBack);
}

function GetWindowLevel_CallBack(result){
	var imageObj = $get('hdSeries').value == "Series2" ? imageComp : image;
	imageObj.set_Window(result.SeriesWindow);
	
	
	imageObj.set_Level(result.SeriesLevel);
}

/****************************************************************************************************
* used  to get any available reports that are associated with a the series
*
******************************************************************************************************/
function GetSeriesReport(comp){
	if(comp)	//comp = true means that I want comparison series report Url, false - primary series report
		$get('hdSeries').value = "Series2";
	PageMethods.GetReportURL(comp, GetSeriesReport_CallBack);
 }

function GetSeriesReport_CallBack(result){
	var control = $get('hdSeries').value == "Series2" ? reportComp : report;

	if (result !=null && result.url != null)
		//set the report URL
		control.set_Url(result.url);
	else
		//disable the report Icon
		control.IconDisable(result.toolTipString);
}

/****************************************************************************************************
* used to check if the session has expired, see global variable 'checkSessionInterval' at the top of this page for the time interval
*
******************************************************************************************************/
function HasSessionExpired(){
	PageMethods.InValidSession(HasSessionExpired_CallBack, onEndRequest);
}


function HasSessionExpired_CallBack(result){
	//check session on the interval for a authentication principle, this is a recursive function that will call itself based on the checkSessionInterval
	if(result == true){
		window.location = 'login.aspx';
		}
}


/****************************************************************************************************
* sets a Session variable to the series in which I am interacting with, either "Series1" or "Series2"
*
******************************************************************************************************/
function MarkActiveWindow(){
	PageMethods.SetActiveWindow($get('hdSeries').value, MarkActiveWindow_CallBack);
}

function MarkActiveWindow_CallBack(){}
/****************************************************************************************************
* used to set up a comparison Object
*
******************************************************************************************************/
function  CreateComparisionObject(create){
	PageMethods.ComparisonObjects(create, CreateComparisionObject_CallBack);
}

function  CreateComparisionObject_CallBack(result){}


/****************************************************************************************************
* used to fetch a guid for the authentication ticket from Novashared and then passed
* to the RIS for view of the report
******************************************************************************************************/
function GetAuthenticationTicket(url){
	PageMethods.FetchAuthenticationTicket(url, GetAuthenticationTicket_CallBack);
}
function GetAuthenticationTicket_CallBack(result){
	if(result!=null){
		window.open(result,"ReportWindow", "scrollbars=yes,menubar=no,width=850px, resizable=yes,location=no,toolbar=no");
		}
		
}


/****************************************************************************************************
* used to get the voice annotations
******************************************************************************************************/
function RetrieveVoiceAnnotation(annotationSelectedToPlay, comparison, series, imgNum, width, height){
	annotation.Reset();
	PageMethods.RetrieveVoiceAnnotation(annotationSelectedToPlay, comparison, series, imgNum, width, height, RetrieveVoiceAnnotation_CallBack);
}
function RetrieveVoiceAnnotation_CallBack(result) {
	if(result.audioAndMousePositions !=null && result.creationDate.length ==1){
		var comparison ="Series1";
		var imageObj = image;
		if(annotation.get_IsComparison()){
		   comparison ="Series2";
		   imageObj = imageComp;
		}
		annotation.set_AudioAndMouseCoordinates(result.audioAndMousePositions);
		mouseMovementsInterval = setInterval('CheckDuration()',1);	
		//$get('MediaPlayer').URL = "VoiceAnnotationLoader.aspx?seriesNum=" + imageObj.get_SeriesNum() +'&imgNum=' + annotation.get_ImageNumber() +"&SplitScreenSeries=" + comparison +"&annotationToPlay=" + annotation.get_SelectedTab();
	    annotation.set_PlayerURL("VoiceAnnotationLoader.aspx?seriesNum=" + imageObj.get_SeriesNum() +'&imgNum=' + annotation.get_ImageNumber() +"&SplitScreenSeries=" + comparison +"&annotationToPlay=" + annotation.get_SelectedTab() );
		annotation.set_CreationDates(result.creationDate);
		annotation.SetUpTabs();
		$get('MediaPlayer').controls.play();
		
	}
	if(result.creationDate.length >1){
		annotation.set_CreationDates(result.creationDate);
		annotation.SetUpTabs();
		}
}
/****************************************************************************************************
* used to create an entry log
*
******************************************************************************************************/
function AuditLogging(comp, msg, details){
	PageMethods.Audit(comp, msg, details, AuditLogging_CallBack);
}

function AuditLogging_CallBack(result){}


function GetLocalizedStrings(){
	PageMethods.GetLocalizedCompressionStrings(GetLocalizedStrings_CallBack)
}


function GetLocalizedStrings_CallBack(result){
	image.set_StringImageCompressed(result)
}
	
 


