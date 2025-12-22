<?php

namespace AsyncAws\Core\Sts;

use AsyncAws\Core\AbstractApi;
use AsyncAws\Core\AwsError\AwsErrorFactoryInterface;
use AsyncAws\Core\AwsError\XmlAwsErrorFactory;
use AsyncAws\Core\RequestContext;
use AsyncAws\Core\Sts\Exception\ExpiredTokenException;
use AsyncAws\Core\Sts\Exception\IDPCommunicationErrorException;
use AsyncAws\Core\Sts\Exception\IDPRejectedClaimException;
use AsyncAws\Core\Sts\Exception\InvalidIdentityTokenException;
use AsyncAws\Core\Sts\Exception\MalformedPolicyDocumentException;
use AsyncAws\Core\Sts\Exception\PackedPolicyTooLargeException;
use AsyncAws\Core\Sts\Exception\RegionDisabledException;
use AsyncAws\Core\Sts\Input\AssumeRoleRequest;
use AsyncAws\Core\Sts\Input\AssumeRoleWithWebIdentityRequest;
use AsyncAws\Core\Sts\Input\GetCallerIdentityRequest;
use AsyncAws\Core\Sts\Result\AssumeRoleResponse;
use AsyncAws\Core\Sts\Result\AssumeRoleWithWebIdentityResponse;
use AsyncAws\Core\Sts\Result\GetCallerIdentityResponse;
use AsyncAws\Core\Sts\ValueObject\PolicyDescriptorType;
use AsyncAws\Core\Sts\ValueObject\ProvidedContext;
use AsyncAws\Core\Sts\ValueObject\Tag;

class StsClient extends AbstractApi
{
    /**
     * Returns a set of temporary security credentials that you can use to access Amazon Web Services resources. These
     * temporary credentials consist of an access key ID, a secret access key, and a security token. Typically, you use
     * `AssumeRole` within your account or for cross-account access. For a comparison of `AssumeRole` with other API
     * operations that produce temporary credentials, see Requesting Temporary Security Credentials [^1] and Compare STS
     * credentials [^2] in the *IAM User Guide*.
     *
     * **Permissions**
     *
     * The temporary security credentials created by `AssumeRole` can be used to make API calls to any Amazon Web Services
     * service with the following exception: You cannot call the Amazon Web Services STS `GetFederationToken` or
     * `GetSessionToken` API operations.
     *
     * (Optional) You can pass inline or managed session policies to this operation. You can pass a single JSON policy
     * document to use as an inline session policy. You can also specify up to 10 managed policy Amazon Resource Names
     * (ARNs) to use as managed session policies. The plaintext that you use for both inline and managed session policies
     * can't exceed 2,048 characters. Passing policies to this operation returns new temporary credentials. The resulting
     * session's permissions are the intersection of the role's identity-based policy and the session policies. You can use
     * the role's temporary credentials in subsequent Amazon Web Services API calls to access resources in the account that
     * owns the role. You cannot use session policies to grant more permissions than those allowed by the identity-based
     * policy of the role that is being assumed. For more information, see Session Policies [^3] in the *IAM User Guide*.
     *
     * When you create a role, you create two policies: a role trust policy that specifies *who* can assume the role, and a
     * permissions policy that specifies *what* can be done with the role. You specify the trusted principal that is allowed
     * to assume the role in the role trust policy.
     *
     * To assume a role from a different account, your Amazon Web Services account must be trusted by the role. The trust
     * relationship is defined in the role's trust policy when the role is created. That trust policy states which accounts
     * are allowed to delegate that access to users in the account.
     *
     * A user who wants to access a role in a different account must also have permissions that are delegated from the
     * account administrator. The administrator must attach a policy that allows the user to call `AssumeRole` for the ARN
     * of the role in the other account.
     *
     * To allow a user to assume a role in the same account, you can do either of the following:
     *
     * - Attach a policy to the user that allows the user to call `AssumeRole` (as long as the role's trust policy trusts
     *   the account).
     * - Add the user as a principal directly in the role's trust policy.
     *
     * You can do either because the role’s trust policy acts as an IAM resource-based policy. When a resource-based
     * policy grants access to a principal in the same account, no additional identity-based policy is required. For more
     * information about trust policies and resource-based policies, see IAM Policies [^4] in the *IAM User Guide*.
     *
     * **Tags**
     *
     * (Optional) You can pass tag key-value pairs to your session. These tags are called session tags. For more information
     * about session tags, see Passing Session Tags in STS [^5] in the *IAM User Guide*.
     *
     * An administrator must grant you the permissions necessary to pass session tags. The administrator can also create
     * granular permissions to allow you to pass only specific session tags. For more information, see Tutorial: Using Tags
     * for Attribute-Based Access Control [^6] in the *IAM User Guide*.
     *
     * You can set the session tags as transitive. Transitive tags persist during role chaining. For more information, see
     * Chaining Roles with Session Tags [^7] in the *IAM User Guide*.
     *
     * **Using MFA with AssumeRole**
     *
     * (Optional) You can include multi-factor authentication (MFA) information when you call `AssumeRole`. This is useful
     * for cross-account scenarios to ensure that the user that assumes the role has been authenticated with an Amazon Web
     * Services MFA device. In that scenario, the trust policy of the role being assumed includes a condition that tests for
     * MFA authentication. If the caller does not include valid MFA information, the request to assume the role is denied.
     * The condition in a trust policy that tests for MFA authentication might look like the following example.
     *
     * `"Condition": {"Bool": {"aws:MultiFactorAuthPresent": true}}`
     *
     * For more information, see Configuring MFA-Protected API Access [^8] in the *IAM User Guide* guide.
     *
     * To use MFA with `AssumeRole`, you pass values for the `SerialNumber` and `TokenCode` parameters. The `SerialNumber`
     * value identifies the user's hardware or virtual MFA device. The `TokenCode` is the time-based one-time password
     * (TOTP) that the MFA device produces.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_temp_request.html
     * [^2]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_sts-comparison.html
     * [^3]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html#policies_session
     * [^4]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html
     * [^5]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html
     * [^6]: https://docs.aws.amazon.com/IAM/latest/UserGuide/tutorial_attribute-based-access-control.html
     * [^7]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html#id_session-tags_role-chaining
     * [^8]: https://docs.aws.amazon.com/IAM/latest/UserGuide/MFAProtectedAPI.html
     *
     * @see https://docs.aws.amazon.com/STS/latest/APIReference/API_AssumeRole.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sts-2011-06-15.html#assumerole
     *
     * @param array{
     *   RoleArn: string,
     *   RoleSessionName: string,
     *   PolicyArns?: null|array<PolicyDescriptorType|array>,
     *   Policy?: null|string,
     *   DurationSeconds?: null|int,
     *   Tags?: null|array<Tag|array>,
     *   TransitiveTagKeys?: null|string[],
     *   ExternalId?: null|string,
     *   SerialNumber?: null|string,
     *   TokenCode?: null|string,
     *   SourceIdentity?: null|string,
     *   ProvidedContexts?: null|array<ProvidedContext|array>,
     *   '@region'?: string|null,
     * }|AssumeRoleRequest $input
     *
     * @throws ExpiredTokenException
     * @throws MalformedPolicyDocumentException
     * @throws PackedPolicyTooLargeException
     * @throws RegionDisabledException
     */
    public function assumeRole($input): AssumeRoleResponse
    {
        $input = AssumeRoleRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'AssumeRole', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'ExpiredTokenException' => ExpiredTokenException::class,
            'MalformedPolicyDocument' => MalformedPolicyDocumentException::class,
            'PackedPolicyTooLarge' => PackedPolicyTooLargeException::class,
            'RegionDisabledException' => RegionDisabledException::class,
        ]]));

        return new AssumeRoleResponse($response);
    }

    /**
     * Returns a set of temporary security credentials for users who have been authenticated in a mobile or web application
     * with a web identity provider. Example providers include the OAuth 2.0 providers Login with Amazon and Facebook, or
     * any OpenID Connect-compatible identity provider such as Google or Amazon Cognito federated identities [^1].
     *
     * > For mobile applications, we recommend that you use Amazon Cognito. You can use Amazon Cognito with the Amazon Web
     * > Services SDK for iOS Developer Guide [^2] and the Amazon Web Services SDK for Android Developer Guide [^3] to
     * > uniquely identify a user. You can also supply the user with a consistent identity throughout the lifetime of an
     * > application.
     * >
     * > To learn more about Amazon Cognito, see Amazon Cognito identity pools [^4] in *Amazon Cognito Developer Guide*.
     *
     * Calling `AssumeRoleWithWebIdentity` does not require the use of Amazon Web Services security credentials. Therefore,
     * you can distribute an application (for example, on mobile devices) that requests temporary security credentials
     * without including long-term Amazon Web Services credentials in the application. You also don't need to deploy
     * server-based proxy services that use long-term Amazon Web Services credentials. Instead, the identity of the caller
     * is validated by using a token from the web identity provider. For a comparison of `AssumeRoleWithWebIdentity` with
     * the other API operations that produce temporary credentials, see Requesting Temporary Security Credentials [^5] and
     * Compare STS credentials [^6] in the *IAM User Guide*.
     *
     * The temporary security credentials returned by this API consist of an access key ID, a secret access key, and a
     * security token. Applications can use these temporary security credentials to sign calls to Amazon Web Services
     * service API operations.
     *
     * **Session Duration**
     *
     * By default, the temporary security credentials created by `AssumeRoleWithWebIdentity` last for one hour. However, you
     * can use the optional `DurationSeconds` parameter to specify the duration of your session. You can provide a value
     * from 900 seconds (15 minutes) up to the maximum session duration setting for the role. This setting can have a value
     * from 1 hour to 12 hours. To learn how to view the maximum value for your role, see Update the maximum session
     * duration for a role [^7] in the *IAM User Guide*. The maximum session duration limit applies when you use the
     * `AssumeRole*` API operations or the `assume-role*` CLI commands. However the limit does not apply when you use those
     * operations to create a console URL. For more information, see Using IAM Roles [^8] in the *IAM User Guide*.
     *
     * **Permissions**
     *
     * The temporary security credentials created by `AssumeRoleWithWebIdentity` can be used to make API calls to any Amazon
     * Web Services service with the following exception: you cannot call the STS `GetFederationToken` or `GetSessionToken`
     * API operations.
     *
     * (Optional) You can pass inline or managed session policies [^9] to this operation. You can pass a single JSON policy
     * document to use as an inline session policy. You can also specify up to 10 managed policy Amazon Resource Names
     * (ARNs) to use as managed session policies. The plaintext that you use for both inline and managed session policies
     * can't exceed 2,048 characters. Passing policies to this operation returns new temporary credentials. The resulting
     * session's permissions are the intersection of the role's identity-based policy and the session policies. You can use
     * the role's temporary credentials in subsequent Amazon Web Services API calls to access resources in the account that
     * owns the role. You cannot use session policies to grant more permissions than those allowed by the identity-based
     * policy of the role that is being assumed. For more information, see Session Policies [^10] in the *IAM User Guide*.
     *
     * **Tags**
     *
     * (Optional) You can configure your IdP to pass attributes into your web identity token as session tags. Each session
     * tag consists of a key name and an associated value. For more information about session tags, see Passing Session Tags
     * in STS [^11] in the *IAM User Guide*.
     *
     * You can pass up to 50 session tags. The plaintext session tag keys can’t exceed 128 characters and the values
     * can’t exceed 256 characters. For these and additional limits, see IAM and STS Character Limits [^12] in the *IAM
     * User Guide*.
     *
     * > An Amazon Web Services conversion compresses the passed inline session policy, managed policy ARNs, and session
     * > tags into a packed binary format that has a separate limit. Your request can fail for this limit even if your
     * > plaintext meets the other requirements. The `PackedPolicySize` response element indicates by percentage how close
     * > the policies and tags for your request are to the upper size limit.
     *
     * You can pass a session tag with the same key as a tag that is attached to the role. When you do, the session tag
     * overrides the role tag with the same key.
     *
     * An administrator must grant you the permissions necessary to pass session tags. The administrator can also create
     * granular permissions to allow you to pass only specific session tags. For more information, see Tutorial: Using Tags
     * for Attribute-Based Access Control [^13] in the *IAM User Guide*.
     *
     * You can set the session tags as transitive. Transitive tags persist during role chaining. For more information, see
     * Chaining Roles with Session Tags [^14] in the *IAM User Guide*.
     *
     * **Identities**
     *
     * Before your application can call `AssumeRoleWithWebIdentity`, you must have an identity token from a supported
     * identity provider and create a role that the application can assume. The role that your application assumes must
     * trust the identity provider that is associated with the identity token. In other words, the identity provider must be
     * specified in the role's trust policy.
     *
     * ! Calling `AssumeRoleWithWebIdentity` can result in an entry in your CloudTrail logs. The entry includes the Subject
     * ! [^15] of the provided web identity token. We recommend that you avoid using any personally identifiable information
     * ! (PII) in this field. For example, you could instead use a GUID or a pairwise identifier, as suggested in the OIDC
     * ! specification [^16].
     *
     * For more information about how to use OIDC federation and the `AssumeRoleWithWebIdentity` API, see the following
     * resources:
     *
     * - Using Web Identity Federation API Operations for Mobile Apps [^17] and Federation Through a Web-based Identity
     *   Provider [^18].
     * - Amazon Web Services SDK for iOS Developer Guide [^19] and Amazon Web Services SDK for Android Developer Guide
     *   [^20]. These toolkits contain sample apps that show how to invoke the identity providers. The toolkits then show
     *   how to use the information from these providers to get and use temporary security credentials.
     *
     * [^1]: https://docs.aws.amazon.com/cognito/latest/developerguide/cognito-identity.html
     * [^2]: http://aws.amazon.com/sdkforios/
     * [^3]: http://aws.amazon.com/sdkforandroid/
     * [^4]: https://docs.aws.amazon.com/cognito/latest/developerguide/cognito-identity.html
     * [^5]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_temp_request.html
     * [^6]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_sts-comparison.html
     * [^7]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_update-role-settings.html#id_roles_update-session-duration
     * [^8]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_use.html
     * [^9]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html#policies_session
     * [^10]: https://docs.aws.amazon.com/IAM/latest/UserGuide/access_policies.html#policies_session
     * [^11]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html
     * [^12]: https://docs.aws.amazon.com/IAM/latest/UserGuide/reference_iam-limits.html#reference_iam-limits-entity-length
     * [^13]: https://docs.aws.amazon.com/IAM/latest/UserGuide/tutorial_attribute-based-access-control.html
     * [^14]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_session-tags.html#id_session-tags_role-chaining
     * [^15]: http://openid.net/specs/openid-connect-core-1_0.html#Claims
     * [^16]: http://openid.net/specs/openid-connect-core-1_0.html#SubjectIDTypes
     * [^17]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_roles_providers_oidc_manual.html
     * [^18]: https://docs.aws.amazon.com/IAM/latest/UserGuide/id_credentials_temp_request.html#api_assumerolewithwebidentity
     * [^19]: http://aws.amazon.com/sdkforios/
     * [^20]: http://aws.amazon.com/sdkforandroid/
     *
     * @see https://docs.aws.amazon.com/STS/latest/APIReference/API_AssumeRoleWithWebIdentity.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sts-2011-06-15.html#assumerolewithwebidentity
     *
     * @param array{
     *   RoleArn: string,
     *   RoleSessionName: string,
     *   WebIdentityToken: string,
     *   ProviderId?: null|string,
     *   PolicyArns?: null|array<PolicyDescriptorType|array>,
     *   Policy?: null|string,
     *   DurationSeconds?: null|int,
     *   '@region'?: string|null,
     * }|AssumeRoleWithWebIdentityRequest $input
     *
     * @throws ExpiredTokenException
     * @throws IDPCommunicationErrorException
     * @throws IDPRejectedClaimException
     * @throws InvalidIdentityTokenException
     * @throws MalformedPolicyDocumentException
     * @throws PackedPolicyTooLargeException
     * @throws RegionDisabledException
     */
    public function assumeRoleWithWebIdentity($input): AssumeRoleWithWebIdentityResponse
    {
        $input = AssumeRoleWithWebIdentityRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'AssumeRoleWithWebIdentity', 'region' => $input->getRegion(), 'exceptionMapping' => [
            'ExpiredTokenException' => ExpiredTokenException::class,
            'IDPCommunicationError' => IDPCommunicationErrorException::class,
            'IDPRejectedClaim' => IDPRejectedClaimException::class,
            'InvalidIdentityToken' => InvalidIdentityTokenException::class,
            'MalformedPolicyDocument' => MalformedPolicyDocumentException::class,
            'PackedPolicyTooLarge' => PackedPolicyTooLargeException::class,
            'RegionDisabledException' => RegionDisabledException::class,
        ]]));

        return new AssumeRoleWithWebIdentityResponse($response);
    }

    /**
     * Returns details about the IAM user or role whose credentials are used to call the operation.
     *
     * > No permissions are required to perform this operation. If an administrator attaches a policy to your identity that
     * > explicitly denies access to the `sts:GetCallerIdentity` action, you can still perform this operation. Permissions
     * > are not required because the same information is returned when access is denied. To view an example response, see I
     * > Am Not Authorized to Perform: iam:DeleteVirtualMFADevice [^1] in the *IAM User Guide*.
     *
     * [^1]: https://docs.aws.amazon.com/IAM/latest/UserGuide/troubleshoot_general.html#troubleshoot_general_access-denied-delete-mfa
     *
     * @see https://docs.aws.amazon.com/STS/latest/APIReference/API_GetCallerIdentity.html
     * @see https://docs.aws.amazon.com/aws-sdk-php/v3/api/api-sts-2011-06-15.html#getcalleridentity
     *
     * @param array{
     *   '@region'?: string|null,
     * }|GetCallerIdentityRequest $input
     */
    public function getCallerIdentity($input = []): GetCallerIdentityResponse
    {
        $input = GetCallerIdentityRequest::create($input);
        $response = $this->getResponse($input->request(), new RequestContext(['operation' => 'GetCallerIdentity', 'region' => $input->getRegion()]));

        return new GetCallerIdentityResponse($response);
    }

    protected function getAwsErrorFactory(): AwsErrorFactoryInterface
    {
        return new XmlAwsErrorFactory();
    }

    protected function getEndpointMetadata(?string $region): array
    {
        if (null === $region) {
            return [
                'endpoint' => 'https://sts.amazonaws.com',
                'signRegion' => 'us-east-1',
                'signService' => 'sts',
                'signVersions' => ['v4'],
            ];
        }

        switch ($region) {
            case 'cn-north-1':
            case 'cn-northwest-1':
                return [
                    'endpoint' => "https://sts.$region.amazonaws.com.cn",
                    'signRegion' => $region,
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-east-1-fips':
                return [
                    'endpoint' => 'https://sts-fips.us-east-1.amazonaws.com',
                    'signRegion' => 'us-east-1',
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-east-2-fips':
                return [
                    'endpoint' => 'https://sts-fips.us-east-2.amazonaws.com',
                    'signRegion' => 'us-east-2',
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-west-1-fips':
                return [
                    'endpoint' => 'https://sts-fips.us-west-1.amazonaws.com',
                    'signRegion' => 'us-west-1',
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-west-2-fips':
                return [
                    'endpoint' => 'https://sts-fips.us-west-2.amazonaws.com',
                    'signRegion' => 'us-west-2',
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-gov-east-1-fips':
                return [
                    'endpoint' => 'https://sts.us-gov-east-1.amazonaws.com',
                    'signRegion' => 'us-gov-east-1',
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-gov-west-1-fips':
                return [
                    'endpoint' => 'https://sts.us-gov-west-1.amazonaws.com',
                    'signRegion' => 'us-gov-west-1',
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-iso-east-1':
            case 'us-iso-west-1':
                return [
                    'endpoint' => "https://sts.$region.c2s.ic.gov",
                    'signRegion' => $region,
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-isof-east-1':
            case 'us-isof-south-1':
                return [
                    'endpoint' => "https://sts.$region.csp.hci.ic.gov",
                    'signRegion' => $region,
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'eu-isoe-west-1':
                return [
                    'endpoint' => 'https://sts.eu-isoe-west-1.cloud.adc-e.uk',
                    'signRegion' => 'eu-isoe-west-1',
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
            case 'us-isob-east-1':
                return [
                    'endpoint' => 'https://sts.us-isob-east-1.sc2s.sgov.gov',
                    'signRegion' => 'us-isob-east-1',
                    'signService' => 'sts',
                    'signVersions' => ['v4'],
                ];
        }

        return [
            'endpoint' => "https://sts.$region.amazonaws.com",
            'signRegion' => $region,
            'signService' => 'sts',
            'signVersions' => ['v4'],
        ];
    }

    protected function getServiceCode(): string
    {
        @trigger_error('Using the client with an old version of Core is deprecated. Run "composer update async-aws/core".', \E_USER_DEPRECATED);

        return 'sts';
    }

    protected function getSignatureScopeName(): string
    {
        @trigger_error('Using the client with an old version of Core is deprecated. Run "composer update async-aws/core".', \E_USER_DEPRECATED);

        return 'sts';
    }

    protected function getSignatureVersion(): string
    {
        @trigger_error('Using the client with an old version of Core is deprecated. Run "composer update async-aws/core".', \E_USER_DEPRECATED);

        return 'v4';
    }
}
