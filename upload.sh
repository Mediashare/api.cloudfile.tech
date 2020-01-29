#!/bin/sh
# Upload file(s) to CloudFile server with fzf command line tools.
help() {
   echo ""
   echo "Usage: $0"
   echo "\t-H Host CloudFile server"
   echo "\t-h Helper"
   echo ""
   echo "Description: Upload file(s) to CloudFile server with fzf command line tools."
   echo "Requierements:"
   echo "\t- fzf (https://github.com/junegunn/fzf#installation)"
   exit 1 # Exit script after printing help
}

while getopts "h:H:" opt
do
   case "$opt" in
      h ) help ;;
      H ) host="$OPTARG" ;;
   esac
done

# Default arguments
if [ -z "$host" ] 
then
	host="https://cloudfile.tech"
fi

# Run
fzf -m | xargs -I {} curl \
    -F "file=@{}" \
    $host/upload
exit 1