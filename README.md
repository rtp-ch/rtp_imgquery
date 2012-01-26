#Responsive Images for TYPO3

rtp_imgquery is a TYPO3 extension that adds responsive and fluid image techniques to the TypoScript IMAGE object, the default image content elements ("Text & Images", "Images Only") as well as the standard [Smarty](https://github.com/rtp-ch/smarty) and Fluid image view helpers.

It is designed to:

* Offer easy and powerful customizations.
* Combine responsive image and fluid image techniques.
* Only load a single image and avoid rendering race conditions.
* Work without any additional javascript libraries (e.g. jQuery).
* Execute as quickly as possible.
* Work without javascript (graceful degradation).

##Approach
The extension uses a modified version of the the [noscript technique](http://www.cloudfour.com/responsive-imgs-part-2/#toc-anchor-1977-22). Instead of loading the correct image after the DOM ready event (see [noscript example](http://www.monoliitti.com/images/)) it inserts the correct image using *document.write* while the page is still loading. In addition, images are given a width of 100% via the style attribute, making them fluid.

###How it Works
1. Based on the predefined breakpoint/width settings for an image the extension creates a list of image versions that need to be generated.
2. The extension instructs TYPO3 to prepare these images.
3. The extension populates an HTML/JavaScript snippet with the list of image versions and inserts this snippet in place of the original image tag.
4. The HTML/JavaScript snippet decides which image version to apply while the page is loading.

> The layout of the HTML snippet and the inline JavaScript code that the extension uses can be found in **Resources/Private/Templates**.

##Installation
1. Clone the extension to your typo3conf extension folder:

		git clone git://github.com/rtp-ch/rtp_imgquery.git typo3conf/ext/rtp_imgquery

2. Install the extension using the extension manager.
3. Add the TypoScript setup to your template: Template > Info/Modify > Includes > Include static (from extensions) > Responsive Images (rtp_imgquery).

##Configuration

###Basic Examples

The following examples will create the four versions of the image fileadmin/images/myimage.jpg.

Screen width  | Image version
--------------|--------------
Above 600 | Default image (width = 800)
Between 400 and 600 | Version of the image with a width of 500
Between 400 and 320 | Version of the image with a width of 280
Less than 320 | Version of the image with a width of 160

####TypoScript

	10 = IMAGE
	10.file = fileadmin/images/myimage.jpg
	10.file.width = 800
	10.breakpoint = 1200
	10.breakpoints = 600:500, 400:280, 320:160

> The IMAGE content object has been extended to accept breakpoint options. The "breakpoint" setting defines the default breakpoint for the IMAGE object. The "breakpoints" setting contains additional screen width / image width instructions.

####Smarty Plugin

    {image
        file = "fileadmin/images/myimage.jpg"
        file.width = "800"
        breakpoint = 1200
        breakpoints = 600:500, 400:280, 320:160
    }

> Because the smarty extension already understands TypoScript there's no special responsive image plugin for smarty. Any valid TypoScript IMAGE setting can be passed as a parameter to the image plugin.

####Fluid View Helper Example

    {namespace responsive=Tx_RtpImgquery_ViewHelpers}
    <responsive:image src="fileadmin/images/myimage.jpg" alt="alt text" breakpoint="900" breakpoints="600:500, 400:280, 320:160" />

> Adding the extension namespace "Tx_RtpImgquery_ViewHelpers" to a Fluid template will extend the standard Fluid image view helper.

####Text & Images Content Element

![*Breakpoint settings for images in content elements*](https://github.com/rtp-ch/rtp_imgquery/raw/master/Documentation/Images/content_element.png)

> The extension TypoScript in **Configuration/TypoScript/** contains default breakpoint settings for image content elements.

###Advanced Configuration Options

*coming soon...*

##Recommended Reading
* [Responsive IMGs — Part 1, by Jason Grigsby](http://www.cloudfour.com/responsive-imgs/)
* [Responsive IMGs Part 2 — In-depth Look at Techniques, by Jason Grigsby](http://www.cloudfour.com/responsive-imgs-part-2/)
* [Responsive IMGs Part 3 — Future of the IMG Tag, by Jason Grigsby](http://www.cloudfour.com/responsive-imgs-part-3-future-of-the-img-tag/)
* [Google spreadsheet reviewing different solutions, by Jason Grigsby](https://docs.google.com/spreadsheet/ccc?key=0AisdYBkuKzZ9dHpzSmd6ZTdhbDdoN21YZ29WRVdlckE&hl=en_US#gid=0)
* [Creating responsive images using the noscript tag, by Mairead Buchan](http://www.headlondon.com/our-thoughts/technology/posts/creating-responsive-images-using-the-noscript-tag)
* [Demo of the noscript approach, by Antti Peisa](http://www.monoliitti.com/images/)
