module.exports = function (grunt) {

    grunt.initConfig
    ({

        md: {
            documentation: {
                src: 'docs/**/*.md',
                dest: 'docs/html/',
                options: {
                    flatten: true,
                    wrapper: 'docs/html/wrapper/wrapper.html',

                    mm: {
                        context: {
                            title: 'Charon REST Framework',
                            description: 'Charon is a framework for building self documented RESTfull API\'s.',
                            keywords: 'Charon rest api framework swagger'
                        }
                    }
                }
            }
        }

    });

    grunt.loadNpmTasks('grunt-md');

    grunt.registerTask('documentation', [
        'md:documentation'
    ]);

    grunt.registerTask('default', [ 'documentation' ]);

};
