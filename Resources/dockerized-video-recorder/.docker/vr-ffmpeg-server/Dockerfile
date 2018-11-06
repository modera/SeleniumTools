FROM modera/php7-fpm

RUN apt-get update && apt-get install ffmpeg libav-tools x264 x265 tmux -y && apt-get clean && rm -rf /var/lib/apt/lists/*