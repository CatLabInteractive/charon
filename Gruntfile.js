module.exports = function (grunt) {

    var documents = grunt.file.expand({filter: "isFile", cwd: "docs"}, ["*.md"]);
    for (var i = 0; i < documents.length; i ++) {
        var name = documents[i].substr(0, documents[i].length -3);
        documents[i] = {
            name: name,
            url: name
        };
        documents[i].name = documents[i].name.replace(/_/g, ' ');
    }

    console.log(documents);

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
                            title: 'Charon API',
                            description: 'Charon is a framework for building self documented RESTful API\'s.',
                            keywords: 'Charon rest api framework swagger',
                            documents: documents
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
