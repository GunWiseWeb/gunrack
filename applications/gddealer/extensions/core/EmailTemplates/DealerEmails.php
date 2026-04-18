<?php
/**
 * @brief       GD Dealer Manager — Email Templates Extension
 * @package     IPS Community Suite
 * @subpackage  GD Dealer Manager
 * @since       16 Apr 2026
 *
 * Provides transactional email templates for dealer lifecycle events:
 * trial expired, trial expiring soon, and welcome email.
 */

namespace IPS\gddealer\extensions\core\EmailTemplates;

use function defined;

if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class DealerEmails
{
	public function get(): array
	{
		return [
			'trialExpired' => [
				'subject' => 'Your GunRack.deals dealer trial has expired',
				'body'    => "Hi {name},\n\nYour free trial period on GunRack.deals has ended and your listings have been suspended.\n\nTo reactivate your listings and continue appearing in price comparisons, subscribe to one of our dealer plans.\n\n{subscribe_url}\n\nIf you have any questions, contact us at {contact_email}.\n\nThanks,\nThe GunRack.deals Team",
			],
			'trialExpiringSoon' => [
				'subject' => 'Your GunRack.deals trial expires in {days_left} days',
				'body'    => "Hi {name},\n\nYour free trial on GunRack.deals expires in {days_left} days on {expiry_date}.\n\nSubscribe now to keep your listings live and continue appearing in search results.\n\n{subscribe_url}\n\nQuestions? Contact us at {contact_email}.\n\nThanks,\nThe GunRack.deals Team",
			],
			'dealerWelcome' => [
				'subject' => 'Welcome to GunRack.deals — Your dealer account is ready',
				'body'    => "Hi {name},\n\nYour dealer account on GunRack.deals has been set up.\n\nYour API Key: {api_key}\n\nYour Public Profile: {profile_url}\n\nShare this link with your customers so they can find your listings and leave reviews.\n\nNext steps:\n1. Log in and go to your Dealer Dashboard\n2. Click Feed Settings and enter your product feed URL\n3. Run your first import to start appearing in price comparisons\n\nNeed help? Visit your Help & Setup tab or contact us at {contact_email}.\n\nThanks,\nThe GunRack.deals Team",
			],
			'disputeNotify' => [
				'subject' => '{dealer_name} has contested your review on GunRack.deals',
				'body'    => "Hi {name},\n\n{dealer_name} has contested the review you left and provided evidence.\n\nTheir reason: {reason}\n\nYou have until {deadline} to respond with your own evidence, or the dispute will be automatically resolved in the dealer's favor.\n\nRespond here: {respond_url}\n\nIf you do not respond within 30 days your review will be marked as resolved.\n\nGunRack.deals Team",
			],
			'disputeAdminNotify' => [
				'subject' => 'Dealer dispute ready for admin review — GunRack.deals',
				'body'    => "A dealer review dispute has received a customer response and is ready for admin review.\n\nLog in to review: {admin_url}\n\nGunRack.deals Team",
			],
			'disputeDismissed' => [
				'subject' => 'Your review contest has been dismissed — GunRack.deals',
				'body'    => "Hi {name},\n\nAfter reviewing the evidence submitted by both parties, admin has decided to keep the customer review as-is.\n\nThis decision is final and the review cannot be contested again.\n\nGunRack.deals Team",
			],
			'disputeAutoResolved' => [
				'subject' => 'Your review dispute has been auto-resolved — GunRack.deals',
				'body'    => "Hi {name},\n\nThe 30-day response window for the contested review on GunRack.deals has passed without a response.\n\nThe dispute has been automatically resolved in the dealer's favor. The review will no longer affect their rating average.\n\nGunRack.deals Team",
			],
			'disputeUpheld' => [
				'subject' => 'Your review contest has been upheld — GunRack.deals',
				'body'    => "Hi {name},\n\nAfter reviewing the evidence submitted by both parties, admin has ruled in your favor.\n\nThe customer review will remain visible but no longer affects your rating average.\n\nGunRack.deals Team",
			],
			'disputeOutcome' => [
				'subject' => 'Review dispute resolved — GunRack.deals',
				'body'    => "Hi {name},\n\nA review dispute on {dealer_name} has been resolved.\n\n{outcome}\n\nThis decision is final.\n\nGunRack.deals Team",
			],
			'dealerResponded' => [
				'subject' => '{dealer_name} responded to your review on GunRack.deals',
				'body'    => "Hi {name},\n\n{dealer_name} has posted a public response to your review.\n\nTheir response:\n{response}\n\nView your review: {profile_url}\n\nGunRack.deals Team",
			],
			'newDealerReview' => [
				'subject' => 'New review on your GunRack.deals dealer profile',
				'body'    => "Hi {name},\n\n{reviewer_name} left a review on your {dealer_name} profile.\n\nView your reviews: {review_url}\n\nGunRack.deals Team",
			],
		];
	}
}
