<?php
namespace Aws\ChimeSDKMeetings;

use Aws\AwsClient;

/**
 * This client is used to interact with the **Amazon Chime SDK Meetings** service.
 * @method \Aws\Result batchCreateAttendee(array $args = [])
 * @method \GuzzleHttp\Promise\Promise batchCreateAttendeeAsync(array $args = [])
 * @method \Aws\Result createAttendee(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createAttendeeAsync(array $args = [])
 * @method \Aws\Result createMeeting(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createMeetingAsync(array $args = [])
 * @method \Aws\Result createMeetingWithAttendees(array $args = [])
 * @method \GuzzleHttp\Promise\Promise createMeetingWithAttendeesAsync(array $args = [])
 * @method \Aws\Result deleteAttendee(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteAttendeeAsync(array $args = [])
 * @method \Aws\Result deleteMeeting(array $args = [])
 * @method \GuzzleHttp\Promise\Promise deleteMeetingAsync(array $args = [])
 * @method \Aws\Result getAttendee(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getAttendeeAsync(array $args = [])
 * @method \Aws\Result getMeeting(array $args = [])
 * @method \GuzzleHttp\Promise\Promise getMeetingAsync(array $args = [])
 * @method \Aws\Result listAttendees(array $args = [])
 * @method \GuzzleHttp\Promise\Promise listAttendeesAsync(array $args = [])
 * @method \Aws\Result startMeetingTranscription(array $args = [])
 * @method \GuzzleHttp\Promise\Promise startMeetingTranscriptionAsync(array $args = [])
 * @method \Aws\Result stopMeetingTranscription(array $args = [])
 * @method \GuzzleHttp\Promise\Promise stopMeetingTranscriptionAsync(array $args = [])
 */
class ChimeSDKMeetingsClient extends AwsClient {}
