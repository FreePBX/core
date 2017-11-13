// grunt-move
// https://github.com/prantlf/grunt-move
//
// Copyright (c) 2017 Ferdinand Prantl
// Licensed under the MIT license.
//
// Grunt task for moving and renaming files and directories

'use strict';

var fs = require('fs'),
    path = require('path'),
    chalk = require('chalk'),
    async = require('async');

module.exports = function (grunt) {

  grunt.registerMultiTask('move', 'Moves and renames files and directories.', function () {
    var done = this.async(),
        options = this.options({
          ignoreMissing: false,
          moveAcrossVolumes: false
        }),
        files = this.files,
        moved = 0,
        failed = 0;

    if (!files.length) {
      if (!options.ignoreMissing) {
        grunt.fail.warn('No files or directories specified.');
      }
      return done();
    }

    function moveAll(file, allDone) {
      if (!file.src.length) {
        if (!options.ignoreMissing) {
          grunt.fail.warn('No files or directories found at ' +
              chalk.cyan(file.orig.src) + '.');
        }
        return allDone();
      }

      function moveOne(src, oneDone) {
        var dest = file.dest,
            trailingChar = dest[dest.length - 1],
            dir;

        if ((trailingChar === '/' || trailingChar === path.sep) &&
            !file.orig.expand) {
          dir = dest.substring(0, dest.length - 1);
          dest = path.join(dest, path.basename(src));
        } else {
          dir = path.dirname(dest);
        }

        grunt.file.mkdir(dir);

        if (dir === path.dirname(src)) {
          grunt.verbose.writeln('Renaming ' + chalk.cyan(src) + ' to ' +
              chalk.cyan(path.basename(dest)) + '.');
        } else {
          grunt.verbose.writeln('Moving ' + chalk.cyan(src) + ' to ' +
              chalk.cyan(dest) + '.');
        }

        fs.rename(src, dest, function (err) {
          if (err) {
            if (err.code === 'EXDEV') {
              if (options.moveAcrossVolumes) {
                grunt.verbose.writeln('Copying ' + chalk.cyan(src) + ' across devices.');
                grunt.file.copy(src, dest);

                grunt.verbose.writeln('Deleting ' + chalk.cyan(src) + '.');
                grunt.file.delete(src);
                ++moved;
              } else {
                grunt.verbose.error();
                grunt.log.error(err);
                grunt.fail.warn('Moving files across devices has not been enabled.');
                ++failed;
              }
            } else {
              grunt.verbose.error();
              grunt.log.error(err);
              grunt.fail.warn('Moving failed.');
              ++failed;
            }
          } else {
            ++moved;
          }
          oneDone();
        });
      }

      async.eachSeries(file.src, moveOne, allDone);
    }

    async.eachSeries(files, moveAll, function (err) {
      grunt.log.ok(moved + ' ' + grunt.util.pluralize(moved, 'file/files') +
          ' or ' + grunt.util.pluralize(moved, 'directory/directories') + ' moved, ' +
          failed + ' failed.');
      done(err);
    });
  });

};
