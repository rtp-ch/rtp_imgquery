#Responsive Images for TYPO3
##Requirements
###General Considerations
* Offers easy and powerful customizations.
* Combines responsive image and fluid image techniques.
* Does not load other images and avoids rendering race conditions.
* Does not require additional javascript libraries (e.g. jQuery).
* Executes as quickly as possible (does not have to wait for the DOM ready event).
* Works without javascript (graceful degradation).

###Features
* Breakpoint options for TypoScript IMAGE objects.
* Fluid View Helper.
* Smarty plugin.
* Breakpoint options for the standard image content objects ("Text & Images", "Images Only")

###Recommended Reading
* [Responsive IMGs, by Jason Grigsby](http://www.cloudfour.com/responsive-imgs-part-2/)
* [Google spreadsheet reviewing different solutions](https://docs.google.com/spreadsheet/ccc?key=0AisdYBkuKzZ9dHpzSmd6ZTdhbDdoN21YZ29WRVdlckE&hl=en_US#gid=0)

###TypoScript
Options to define breakpoints and their corresponding

	10 = IMAGE
	10.file = fileadmin/images/myimage.jpg
	10.file.width = 800
	10.breakpoint = 1200
	10.breakpoints = 600:400,400:280,320:160


Or they can be derived from the defined breakpoints (i.e. the responsive image widths will correspond to the defined breakpoints):

    10 = IMAGE
    10.file = fileadmin/images/myimage.jpg
    10.file.width = 1000
    10.breakpoints = 720,400

###Fluid ViewHelper

The Fluid ViewHelper is an extension of the normal image view helper which accepts a list of breakpoints as an additional parameter:

	<f:imgQuery src="fileadmin/images/myimage.jpg" width="1000" breakpoints="720,400" />

###Smarty Plugin

The Smarty plugin is the normal image plugin as it applues the IMAGE content object: 

	{image file="fileadmin/images/myimage.jpg" file.width="1000" breakpoints.720.file.width="720" breakpoints.400.file.width="400"}

	{image file="fileadmin/images/myimage.jpg" file.width="1000" breakpoints="720,400"}


##Specifications for rtp_imgquery JS

###HTML Application

    <noscript>
    	<img src="/images/img/portrait-xlarge.gif" width="714" height="956" alt="" style="width: 100%; height: auto;" />
    </noscript>
    <script>
        var options = {
            "460" : {
                "src" : "/images/img/landscape-small.gif",
                "width" : 460,
                "height" : 280,
                "alt" : "Different ALT attribute at breakpoint 460!"
            },
            "1024" : {
                "src" : "/images/img/landscape-medium.gif",
                "width" : 1024,
                "height" : 624,
                "style" : "min-width: 100%;"
            }
        };
        var defaultImage = '<img src="/images/img/portrait-xlarge.gif" width="714" height="956" alt="" style="width: 100%; height: auto;" />';
    	document.write(imgQuery(defaultImage, options).img());
    </script>

> *Note: The global variables "options" and "defaultImage" are purely for readability, the correct solution would include the options and the default image as function arguments.*

####Alternative HTML Application

    responsiveImg = new imgQuery(defaultImage, options);
    document.write(unescape('%3Cimg src="' + responsiveImg.attr('src') + '" width="' + responsiveImg.attr('width') + '" height="' + responsiveImg.attr('width') + '" alt="" style="width: 100%; height: auto;" /%3E'));

###Public API

* **attr('src')**  *getter* Gets an attribute value of the responsive version of the img tag.
* **attr('src', '/images/img/landscape-small.gif')** *setter* Sets an attribute of the responsive img tag to a given value.
* **img()** *getter* Gets the complete img tag for the current breakpoint from the default img tag and breakpoint options
* **breakpoint()** *getter/setter* Globally sets/gets current breakpoint value
* **screensize()** *getter/setter* Globally sets/gets current screensize value
