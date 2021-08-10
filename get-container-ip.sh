ip=$(docker inspect -f '{{range.NetworkSettings.Networks}}{{.Gateway}}{{end}}' sonar_web_1)
echo "-- ADD THIS TO EXTRA HOSTS --"
echo "1.sonar.dev:$ip"