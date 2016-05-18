sudo ant -u jenkins ant phpunit -f plugins/Translator/vendor/Jenkins/build.xml

wget http://localhost:8080/jnlpJars/jenkins-cli.jar
/usr/lib/java/bin/java -jar jenkins-cli.jar -s http://localhost:8080/ create-job "CakePHP 3 Plugin Translator" < "plugins/Translator/vendor/Jenkins/jobs/CakePHP3-Translator-Plugin.xml"
/usr/lib/java/bin/java -jar jenkins-cli.jar -s http://localhost:8080/ create-job "CakePHP 3 Plugin Translator Quality" < "plugins/Translator/vendor/Jenkins/jobs/CakePHP3-Translator-Plugin-Quality.xml"