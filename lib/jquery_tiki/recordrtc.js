var video = document.querySelector('video');
var audio = document.querySelector('audio');

if(!navigator.getDisplayMedia && !navigator.mediaDevices.getDisplayMedia) {
    var error = tr('Recording WebRTC not supported in this browser.');
    $('.box-recordrtc .card-body').html(error);

    throw new Error(error);
}

var isEdge = navigator.userAgent.indexOf('Edge') !== -1 && (!!navigator.msSaveOrOpenBlob || !!navigator.msSaveBlob);
var isSafari = /^((?!chrome|android).)*safari/i.test(navigator.userAgent);

const VIDEO_STREAM_CONSTRAINTS = {
    video: true,
};

const AUDIO_STREAM_CONSTRAINTS = {
    audio: isEdge ? true : {
        echoCancellation: false
    }
};

const VIDEO_AND_AUDIO_STREAM_CONSTRAINTS = {
    ...AUDIO_STREAM_CONSTRAINTS,
    ...VIDEO_STREAM_CONSTRAINTS
};

// globally accessible
var recorder;
var microphone;
var recordingInstance;

var listOfFilesUploaded = [];

function uploadToServer(recordRTC, callback) {
    var blob = recordRTC instanceof Blob ? recordRTC : recordRTC.blob;
    var fileType = blob.type.split('/')[0] || 'audio';
    var fileName = moment().format('YYYYMMDDhmmss');
    var upload_url = document.getElementById('record-rtc-url').value;
    var ticket = document.getElementById('record-rtc-ticket').value;
    var customFileName = document.getElementById('record-name').value;
    var galleryId = document.getElementById('record-rtc-gallery-id').value;

    if (fileType === 'audio') {
        fileName = customFileName ? customFileName : 'audio_record_' + fileName;
        fileName += '.' + (!!navigator.mozGetUserMedia ? 'ogg' : 'wav');
    } else {
        fileName = customFileName ? customFileName : 'video_record_' + fileName;
        fileName += '.webm';
    }

    // create FormData
    var formData = new FormData();
    formData.append(fileType + 'filename', fileName);
    formData.append(fileType + 'blob', blob);
    formData.append('ticket', ticket);
    formData.append('galleryId', galleryId);

    callback('Uploading ' + fileType + ' recording to server.');

    makeXMLHttpRequest(upload_url, formData, function(progress, response) {
        if (progress !== 'upload-ended') {
            callback(progress);
            return;
        }

        if (!!response) {
            response = JSON.parse(response);
            var fileId = response.fileId;
            var thumbBox = '';
            var fileUrl = '';
            var nextTicket = '';

            if (fileId) {
                thumbBox = '{mediaplayer src="display' + fileId + '"}';
                fileUrl = 'tiki-download_file.php?fileId=' + fileId;
                nextTicket = response.nextTicket;
            }
        }

        var fileData = {
            'thumbBox': thumbBox,
            'fileUrl': fileUrl,
            'fileName': fileName,
            'ticket': nextTicket
        };

        callback('ended', fileData, nextTicket);

        // to make sure we can delete as soon as visitor leaves
        listOfFilesUploaded.push(fileName);
    });
}

function makeXMLHttpRequest(url, data, callback) {
    var request = new XMLHttpRequest();
    request.onreadystatechange = function() {
        if (request.readyState == 4) {
            callback('upload-ended', request.response);
        }
    };

    request.upload.onloadstart = function() {
        callback('Upload started...');
    };

    request.upload.onprogress = function(event) {
        callback('Upload progress ' + Math.round(event.loaded / event.total * 100) + "%");
    };

    request.upload.onload = function() {
        callback('Upload finished');
    };

    request.upload.onerror = function(error) {
        callback('Failed to upload to server');
        console.error('XMLHttpRequest failed', error);
    };

    request.upload.onabort = function(error) {
        callback('Upload aborted.');
        console.error('XMLHttpRequest aborted', error);
    };

    request.open('POST', url);
    request.send(data);
}

function addStreamStopListener(stream, callback) {
    stream.addEventListener('ended', function() {
        callback();
        callback = function() {};
    }, false);
    stream.addEventListener('inactive', function() {
        callback();
        callback = function() {};
    }, false);
    stream.getTracks().forEach(function(track) {
        track.addEventListener('ended', function() {
            callback();
            callback = function() {};
        }, false);
        track.addEventListener('inactive', function() {
            callback();
            callback = function() {};
        }, false);
    });
}

function startUpload() {
    if (recorder) {
        var $feedback = $('#upload-feedback span').text('Uploading... Please wait.').show();
        var autoUpload = $('#record-rtc-auto-upload');
        autoUpload.removeAttr('checked');

        uploadToServer(recorder, function(progress, fileData, ticket) {
            if(progress === 'ended') {
                if (fileData.fileUrl && fileData.fileName) {
                    let extension = fileData.fileName.split('.').pop();
                    $('#record-download a').attr('href', fileData.fileUrl).text(fileData.fileName);
                    $('#record-download span').text('Upload finished ');

                    if (fileData.thumbBox) {
                        $('#record-download').append('<br/><code>' + fileData.thumbBox + '</code>');
                    }

                    if (ticket) {
                        document.getElementById('record-rtc-ticket').value = ticket;
                    }

                    recorder.destroy();
                    recorder = null;
                } else {
                    $('#btn-upload-recording').show();
                    $('#record-download span').html('<p>Something went wrong when uploading this file. Please try again.</p>');
                }
            }
        });
    }
}

/**
 * Request permissions for specific constraints related with the display.
 * @param constraints
 * @param onSuccess
 * @param onFailure
 */
function getDisplayMedia(constraints, onSuccess, onFailure) {
    if(navigator.mediaDevices.getDisplayMedia) {
        navigator.mediaDevices.getDisplayMedia(constraints).then(onSuccess).catch(onFailure);
    }
    else {
        navigator.getDisplayMedia(constraints).then(onSuccess).catch(onFailure);
    }
}

/**
 * Request permissions for specific constraints associated with the media input.
 * @param constraints
 * @param onSuccess
 * @param onFailure
 */
function getUserMedia(constraints, onSuccess, onFailure) {
    if(navigator.mediaDevices.getUserMedia) {
        navigator.mediaDevices.getUserMedia(constraints).then(onSuccess).catch(onFailure);
    }
    else {
        navigator.getUserMedia(constraints).then(onSuccess).catch(onFailure);
    }
}

/**
 * Show recording feedback based on currently recorded blob type
 * @return void
 */
function showRecordingFeedback(recordingType) {
    const blob = recorder.getBlob();
    const fileType = blob.type.split('/')[0] || 'audio';
    const src = URL.createObjectURL(blob);

    var html = '<div>';

    if (fileType === 'audio') {
        html += '<br/><audio style="width: 100%; max-width: 500px" src="' + src + '" controls=""></audio>';
    } else {
        html += '<br/><video style="width: 100%; max-width: 500px" src="' + src + '" controls=""></video>';
    }

    html += '<br/><div id="record-download">' +
        '<span></span>' +
        '<a target="_blank" href=""></a>' +
        '</div>';

    html += '</div>';

    var $feedback = $('#upload-feedback').show();
    $feedback.html(html);
}

class RecordMicrophone {
    constructor() {
        if (!microphone) {
            this.requestPermissions(function(mic) {
                microphone = mic;

                if(isSafari) {
                    replaceAudio();

                    alert('Please click startRecording button again. First time we tried to access your microphone. Now we will record it.');
                }

                var options = {
                    type: 'audio',
                    numberOfAudioChannels: isEdge ? 1 : 2,
                    checkForInactiveTracks: true,
                    bufferSize: 16384
                };

                if(isSafari || isEdge) {
                    options.recorderType = StereoAudioRecorder;
                }

                if(navigator.platform && navigator.platform.toString().toLowerCase().indexOf('win') === -1) {
                    options.sampleRate = 48000; // or 44100 or remove this line for default
                }

                if(isSafari) {
                    options.sampleRate = 44100;
                    options.bufferSize = 4096;
                    options.numberOfAudioChannels = 2;
                }

                if(recorder) {
                    recorder.destroy();
                    recorder = null;
                }

                recorder = RecordRTC(microphone, options);
                recorder.startRecording();

                $('#btn-start-recording').hide().prop('disabled', false);
                $('#btn-stop-recording').show();
            });
        }
    }

    stopRecording() {
        if (recorder && typeof recorder.stopRecording === "function") {
            recorder.stopRecording(function() {
                showRecordingFeedback();

                var autoUpload = $('#record-rtc-auto-upload').is(':checked');

                if (autoUpload == true) {
                    startUpload();
                } else {
                    $('#btn-upload-recording').show();
                }

                $('#btn-start-recording').show();
                $('#btn-stop-recording').hide();

                microphone.stop();
                microphone = null;
            });
        }
    }

    requestPermissions(callback) {
        getUserMedia(AUDIO_STREAM_CONSTRAINTS, function(mic) {
            callback(mic);
        }, function(error) {
            alert('Unable to capture your microphone. Please check console logs.');
            console.error(error);
        });
    }
}

class RecordCameraAndAudio {
    constructor() {
        getUserMedia(VIDEO_AND_AUDIO_STREAM_CONSTRAINTS, function(camera) {
            recorder = RecordRTC(camera, {
                type: 'video'
            });

            recorder.startRecording();
            recorder.camera = camera;

            $('#btn-start-recording').hide().prop('disabled', false);
            $('#btn-stop-recording').show();
        }, function(error) {
            alert('Unable to capture your camera. Please check console logs.');
            console.error(error);
        });
    }

    stopRecording() {
        if (recorder && typeof recorder.stopRecording === "function") {
            recorder.camera.stop();

            recorder.stopRecording(function() {
                showRecordingFeedback();

                var autoUpload = $('#record-rtc-auto-upload').is(':checked');

                if (autoUpload == true) {
                    startUpload();
                } else {
                    $('#btn-upload-recording').show();
                }

                $('#btn-start-recording').show();
                $('#btn-stop-recording').hide();
            });
        }
    }
}

class RecordScreen {
    constructor() {
        this.requestPermissions(function (screen) {
            recorder = RecordRTC(screen, {
                type: 'video'
            });

            recorder.startRecording();

            // release screen on stopRecording
            recorder.screen = screen;

            $('#btn-start-recording').hide().prop('disabled', false);
            $('#btn-stop-recording').show();
        });
    }

    stopRecording() {
        if (recorder && typeof recorder.stopRecording === "function") {
            recorder.screen.stop();

            recorder.stopRecording(function() {
                showRecordingFeedback();

                var autoUpload = $('#record-rtc-auto-upload').is(':checked');

                if (autoUpload == true) {
                    startUpload();
                } else {
                    $('#btn-upload-recording').show();
                }

                $('#btn-start-recording').show();
                $('#btn-stop-recording').hide();
            });
        }
    }

    requestPermissions(callback) {
        getDisplayMedia(VIDEO_STREAM_CONSTRAINTS, function(screen) {
            addStreamStopListener(screen, function() {
                $('#btn-stop-recording').trigger("click");
            });

            callback(screen);
        }, function(error) {
            console.error(error);
            alert('Unable to capture your screen. \n' + error);
            $('#btn-start-recording').show().prop('disabled', false);
        });
    }
}

class RecordScreenAndMicrophone {
    constructor() {
        this.requestPermissions(function (screen) {
            recorder = RecordRTC(screen, {
                type: 'video'
            });

            recorder.startRecording();

            // release screen on stopRecording
            recorder.screen = screen;

            $('#btn-start-recording').hide().prop('disabled', false);
            $('#btn-stop-recording').show();
        });
    }

    stopRecording() {
        recorder.screen.stop();

        if (recorder && typeof recorder.stopRecording === "function") {
            recorder.stopRecording(function() {
                showRecordingFeedback();

                var autoUpload = $('#record-rtc-auto-upload').is(':checked');

                if (autoUpload == true) {
                    startUpload();
                } else {
                    $('#btn-upload-recording').show();
                }

                $('#btn-start-recording').show();
                $('#btn-stop-recording').hide();
                recorder.screen = null;
            });
        }
    }

    requestPermissions(callback) {
        getDisplayMedia(VIDEO_STREAM_CONSTRAINTS, function(screen) {
            getUserMedia(AUDIO_STREAM_CONSTRAINTS, function(mic) {
                screen.addTrack(mic.getTracks()[0]);

                addStreamStopListener(screen, function() {
                    $('#btn-stop-recording').trigger("click");
                });

                callback(screen);
            });
        }, function(error) {
            console.error(error);
            alert('Unable to capture your screen. \n' + error);
            $('#btn-start-recording').show().prop('disabled', false);
        });
    }
}

$('#btn-start-recording').on('click', function () {
    $(this).prop('disabled', true);
    $('#upload-feedback').hide();

    switch ($('#mod_record_rtc_recording_type').val()) {
        case 'screen':
            recordingInstance = new RecordScreen();
            break;
        case 'microphone':
            recordingInstance = new RecordMicrophone();
            break;
        case 'screen,microphone':
            recordingInstance = new RecordScreenAndMicrophone();
            break;
        case 'camera,microphone':
            recordingInstance = new RecordCameraAndAudio();
            break;
        default:
            console.error('Recording type not implemented.');
    }
});

$('#btn-stop-recording').on('click', function(e) {
    e.preventDefault();

    recordingInstance.stopRecording();
});

$('#btn-upload-recording').on('click', function(e) {
    e.preventDefault();
    $('#btn-upload-recording').hide();

    startUpload();
});

$('#mod_record_rtc_recording_type').on('change', function(e) {
    $('#btn-start-recording').prop('disabled', !$(this).val());
});
