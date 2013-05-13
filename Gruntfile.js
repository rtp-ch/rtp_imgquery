/*global module:false*/
module.exports = function (grunt) {
    'use strict';

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),
        jshint: {
            files: ['Resources/Private/Templates/Source/js/**/*.js'],
            options: {
                jshintrc: 'node_modules/PortableCGL/jshint/.jshintrc'
            }
        },
        uglify: {
            dynamic_mappings: {
                files: [
                    {
                        expand: true,
                        cwd: 'Resources/Private/Templates/Source/js/',
                        src: ['**/*.js'],
                        dest: 'Resources/Private/Templates/Build/js/',
                        ext: '.min.js'
                    }
                ]
            }
        },
        compile: {
            source: 'Resources/Private/Templates/Source/html/**/*.html'
        },
        copy: {
            main: {
                files: [
                    {
                        src: ['**'],
                        dest: 'Resources/Private/Templates/Build/test/qunit/',
                        cwd: 'Resources/Private/Templates/Source/test/qunit/',
                        expand: true
                    },
                    {
                        src: ['**'],
                        dest: 'Resources/Private/Templates/Build/test/images/',
                        cwd: 'Resources/Private/Templates/Source/test/images/',
                        expand: true
                    }
                ]
            }
        },
        jade: {
            compile: {
                options: {
                    pretty: true
                },
                files: {
                    'Resources/Private/Templates/Build/test/imgQuery.test.normal.html': 'Resources/Private/Templates/Source/test/imgQuery.test.normal.jade',
                    'Resources/Private/Templates/Build/test/imgQuery.test.small.html': 'Resources/Private/Templates/Source/test/imgQuery.test.small.jade',
                    'Resources/Private/Templates/Build/test/imgQuery.test.large.html': 'Resources/Private/Templates/Source/test/imgQuery.test.large.jade',
                    'Resources/Private/Templates/Build/test/imgQuery.test.cache.html': 'Resources/Private/Templates/Source/test/imgQuery.test.cache.jade'
                }
            }
        },
        qunit: {
            all: ['Resources/Private/Templates/Build/test/*.html']
        }
    });
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-qunit');
    grunt.loadNpmTasks('grunt-contrib-jade');
    grunt.loadNpmTasks('grunt-contrib-copy');
    grunt.registerMultiTask('compile', 'Compile imgQuery HTML Templates.', function () {

        var _ = grunt.util._,
            files = grunt.file.expand(this.data),
            jsFile,
            destination,
            content,
            script;

        files.forEach(function (f) {

            if (!grunt.file.exists(f)) {
                grunt.log.warn('Source file "' + f + '" not found.');
            }

            destination = f.replace('/Source/html/', '/Build/html/');
            jsFile = f.replace('/Source/html/', '/Build/js/').replace(/\.html?$/i, '.min.js');

            if (!grunt.file.exists(jsFile)) {
                grunt.log.warn('Source file "' + jsFile + '" not found.');
            }

            script = grunt.file.read(jsFile)
                .replace(/imagesIn/gi, '###IMAGES###')
                .replace(/breakpointsIn/gi, '###BREAKPOINTS###')
                .replace(/ratiosIn/gi, '###RATIOS###')
                .replace(/cacheKeyIn/gi, '\'###CACHE_KEY###\'');

            content = grunt.file.read(f)
                .replace(/###SCRIPT###/gi, script);

            grunt.file.write(
                f.replace('/Source/html/', '/Build/html/'),
                content
            );

            if (!grunt.file.exists(jsFile)) {
                grunt.log.warn('Target file "' + destination + '" was not compiled.');
            }
        });

        grunt.log.ok(files.length + ' file' + (files.length === 1 ? '' : 's') + ' compiled.');
    });

    grunt.registerTask('build', ['jshint', 'uglify', 'compile', 'copy', 'jade']);
    grunt.registerTask('test', ['qunit']);
    grunt.registerTask('default', ['build', 'test']);
};
