
var audioPlayer = doc.getElementById("audiofile");

audioPlayer.addEventListener("timeupdate", function(e)
{
	syncData.forEach(function(element, index, array)
	{
	    if( audioPlayer.currentTime >= 0.5 && audioPlayer.currentTime <= 1.0 )
	        console.log("time stamp");
	});
});

