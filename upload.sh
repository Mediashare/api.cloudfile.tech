#!/bin/sh
# Upload file(s) to CloudFile server with fzf command line tools.
help() {
   echo ""
   echo "Usage: $0"
   echo "\t-H Host CloudFile server"
   echo "\t-a API key for private cloud"
   echo "\t-h Helper"
   echo ""
   echo "Description: Upload file(s) to CloudFile server with fzf command line tools."
   echo "Requierements:"
   echo "\t- fzf (https://github.com/junegunn/fzf#installation)"
   exit 1 # Exit script after printing help
}

while getopts "h:H:a:" opt
do
   case "$opt" in
      h ) help ;;
      H ) host="$OPTARG" ;;
      a ) api="$OPTARG" ;;
   esac
done

# Default arguments
if [ -z "$host" ] 
then
	host="https://cloudfile.tech"
fi

# Run
if [ -z "$api" ] 
then
   fzf -m | xargs -I {} curl \
      -F "file=@{}" \
      $host/upload
else
   fzf -m | xargs -I {} curl \
      -H "ApiKey: $api" \
      -F "file=@{}" \
      $host/upload
fi
exit 1