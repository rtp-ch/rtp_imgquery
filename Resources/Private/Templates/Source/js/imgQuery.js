/* global imagesIn:false, breakpointsIn:false, ratiosIn:false, cacheKeyIn:false */
({

    /**
     * Inserts HTML (an img tag) into the dom using document.write while the page is loading. Should avoid
     * raciness as the script is inlined and only executed before the page has been parsed, but might degrade
     * performance as content rendering will only continue when the inline script has finished executing.
     *
     * HACK: jshint options are relaxed to enable document.write
     *
     * @param  {string} image The HTML to insert.
     */
    write: function (image) {
        /* jshint strict: false, evil: true */
        if (document.readyState === 'loading') {
            document.write(image);
        }
    },

    /**
     * From a list of values gets the index of the first value which exceeds the target value. For example, given
     * a list of breakpoints and the current window width it will return the first breakpoint in the list which
     * equals or is larger than the current window width.
     *
     * @param  {array} values       List of integers in ascending order
     * @param  {int}   valueToMatch The target value
     * @return {int}                The first value to equal or exceed the target value
     */
    closest: function (values, valueToMatch) {
        'use strict';

        var l = values.length,
            i = 0;

        while (i < l) {

            if (values[i] >= valueToMatch) {
                break; // Exits as soon as the relevant index has been identified.
            }

            i += 1;
        }

        // Ensures that i is still an index of values
        i = (i === l) ? l - 1 : i;

        return i;
    },

    sort: function (a, b) {
        'use strict';

        return (a < b ? -1 : (b > a ? 1 : 0));
    },

    /**
     * Determines the correct image from the current window size, a list of images, a corresponding
     * list of breakpoints and a list of pixel ratios. The "correct" image is inserted into the dom and cached.
     *
     * @param  {object} global The global object (should be "window")
     * @param  {array}  breakpoints A list of breakpoints
     * @param  {object} images A list of HTML img solutions by device pixel ratio and breakpoint
     * @param  {array}  ratios A list of images
     * @param  {string} key Caching ID for the breakpoints/images combo
     */
    imgQry: function (global, breakpoints, images, ratios, key) {
        'use strict';

        breakpoints = breakpoints.sort(this.sort);
        ratios = ratios.sort(this.sort);

        // Initializes global cache
        if (typeof global.imgQry === 'undefined') {
            global.imgQry = {};
        }

        // Initializes global images cache
        if (typeof global.imgQry.images === 'undefined') {
            global.imgQry.images = {};
        }

        // Initializes global ratios cache
        if (typeof global.imgQry.ratios === 'undefined') {
            global.imgQry.ratios = {};
        }

        // Initializes global breakpoints cache
        if (typeof global.imgQry.breakpoints === 'undefined') {
            global.imgQry.breakpoints = {};
        }

        // Caches the initial window width
        if (typeof global.imgQry.width === 'undefined') {
            global.imgQry.width = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth || 0;
        }

        // Caches the initial pixel ratio
        if (typeof global.imgQry.ratio === 'undefined') {
            global.imgQry.ratio = (typeof(global.devicePixelRatio) !== 'undefined') ? global.devicePixelRatio : 1;
        }

        // Caches the correct pixel ratio for the current device and ratios
        if (typeof global.imgQry.ratios[ratios] === 'undefined') {
            global.imgQry.ratios[ratios] = ratios[this.closest(ratios, global.imgQry.ratio)];
        }

        // Caches the correct breakpoint for the current window width and breakpoints
        if (typeof global.imgQry.breakpoints[breakpoints] === 'undefined') {
            global.imgQry.breakpoints[breakpoints] = breakpoints[this.closest(breakpoints, global.imgQry.width)];
        }

        // Gets the proper image for the current breakpoint and pixel ratio
        if (typeof global.imgQry.images[key] === 'undefined') {
            global.imgQry.images[key] = images[global.imgQry.ratios[ratios]][global.imgQry.breakpoints[breakpoints]];
        }

        this.write(global.imgQry.images[key]);
    }
// Do not change the naming of the arguments as these will be regexed when building the TYPO3 template!
}.imgQry(this, breakpointsIn, imagesIn, ratiosIn, cacheKeyIn));
