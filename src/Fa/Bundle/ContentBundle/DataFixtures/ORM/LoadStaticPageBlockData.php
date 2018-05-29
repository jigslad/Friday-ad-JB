<?php

/**
 * This file is part of the fa bundle.
 *
 * @copyright Copyright (c) 2014, FMG
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fa\Bundle\CoreBundle\DataFixtures\ORM;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Fa\Bundle\ContentBundle\Entity\StaticPage;
use Fa\Bundle\ContentBundle\Repository\StaticPageRepository;

/**
 * Load static page block data.
 *
 * @author Samir Amrutya <samiram@aspl.in>
 * @copyright 2014 Friday Media Group Ltd
 * @version 1.0
 */
class LoadStaticPageBlockData extends AbstractFixture implements OrderedFixtureInterface
{

    /**
     * Load data.
     *
     * @param Doctrine\Common\Persistence\ObjectManager $em
     */
    public function load(ObjectManager $em)
    {
        // set class meta data
        $metadata = $em->getClassMetaData('Fa\Bundle\ContentBundle\Entity\StaticPage');
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $staticPageId = 1;
        $description = <<<EOD
        <p>Friday-Ad was founded in 1975 when the current owners realised there was an untapped market for local advertising. Looking to buy a second hand car, they found they had to travel long distances to find one. <br /> <br /> Together, they launched the first edition as a four-page advertiser in Uckfield, Sussex. <br /> <br /> Today, with over 400,000 copies of Friday-Ad printed and over 1 million readers every week, Friday-Ad has become the largest Independent publisher in the UK, with over 20,000 businesses advertising their products and services. There is even an edition on the Costa del Sol in Spain! <br /> <br /> Moving with the times, Friday-Ad.co.uk has developed into one of the UK's leading classified websites with over 362,000 ads online each week and a total of 226,000 visitors per week and 2.5 million page impressions. The Friday-Ad web group comprises 75 websites, covering everything from motors to jobs, pets to property. <br /> <br /> If you would like to advertise, <a href="http://www.friday-ad.co.uk">friday-ad.co.uk/PlaceAnAd</a> is the fastest and easiest way to place an ad. Your ad will appear online within minutes, plus in your local printed edition. <br /><br /> For information on Friday-Ad business, please <a title="Full media pack" href="/contact-us">contact us</a> <br /><br /> Friday-Ad is an environmentally responsible company. We ensure that our business needs are met without compromising the future. <a title="Recycling" href="/recycling">View our recycling activities.</a> <br /><br /> If you have any suggestions, please email us. We love feedback!</p>
<div style="text-align: center;"><a title="Contact Page" href="/contact-us">Pembroke Dock, Pembrokeshire</a> | <a title="Contact Page" href="/contact-us">Sayers Common, West Sussex</a></div>
EOD;
        $staticPage1 = new StaticPage();
        $staticPage1->setId($staticPageId);
        $staticPage1->setTitle("About Us");
        $staticPage1->setDescription($description);
        $staticPage1->setStatus('1');
        $staticPage1->setSlug('about-us');
        $staticPage1->setType(StaticPageRepository::STATIC_PAGE_TYPE_ID);
        $em->persist($staticPage1);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        <ol>
		<li>While every endeavour will be made to meet the wishes of Advertisers, the Publisher does not guarantee the insertion, the position, or the colour of any particular advertisement.<br>&nbsp;<br></li>
		<li>The Publisher does not accept responsibility for any loss or damage caused by (a) an error, inaccuracy or omission in the printing of the advertisement: (b) for any failure to publish an advertisement on the date or dates specified by the Advertiser: (c) for the publication of an advertisement on the date or dates specified by the Advertiser whether the actual date be earlier or later than the date or dates specified: and/or in respect of any loss or damage alleged to have arisen through delay in forwarding or omission to forward replies to Box Numbers to the Advertiser, however caused.<br>&nbsp;<br></li>
		<li>The Publisher reserves the right to omit, suspend, or change the position of any advertisement, even if it has already been accepted for publication.<br>&nbsp;<br></li>
		<li>The Publisher reserves the right to make any alteration it considers necessary or desirable in an advertisement and to require artwork or copy to be amended to meet its approval.<br>&nbsp;<br></li>
		<li>The Advertiser shall be responsible for checking an advertisement on each occasion that it is published. If the Company is shown to have made an error or inaccuracy in the insertion of or omission to insert any advertisement it shall make a refund or adjustment to the cost of the advertisement at a rate agreed between the Company and the Advertiser. No refund or adjustment will be made if the error or inaccuracy does not materially affect the cost or detract from the advertisement.<br>&nbsp;<br></li>
		<li>Cancellation - The Publisher requires fourteen clear days notice in writing of cancellation, or reduction in the advertisement size, of any order or unexpired part of an order, or in the case of an advertisement which by reason of its position is chargeable at a premium rate, not less than twenty eight clear days notice before the insertion or the next insertion on payment of the difference (if any) between the rates for the series specified in the order and the usual price for the series of insertions which has appeared when the order is stopped.<br>&nbsp;<br></li>
		<li>The Publisher reserves the right to require four clear days notice of cancellation of any order or unexpired part of an order, or in the case of an advertisement
		which by reason of its position is chargeable at a premium rate, not less than twenty-eight clear days notice before the insertion or the next insertion on payment
		of the difference (if any) between the rates for the series specified in the order and the usual price for the series of insertions which has appeared when the order is stopped.<br>&nbsp;<br></li>
		<li>The Publisher reserves the right to increase advertisement rates at any time or to amend the terms of contract as regards space or frequency of insertion. In such event the Advertiser has the option of cancelling the balance of the contract without surcharge. If the Advertiser cancels the balance of a contract, except in the circumstances stated all unearned series discounts will be surcharged. The Publisher reserves the right of surcharge in the event of insertions not being completed within the contractual period.<br>&nbsp;<br></li>
		<li>Credit accounts are strictly nett and unless by prior agreement will be pre-paid. Where credit agreement exists our terms are that our account must be settled within 28 days of the date of the advertisement. If the account is overdue, the Publisher reserves the right to suspend insertions.<br>&nbsp;<br></li>
		<li>The Advertiser/Advertising Agency agrees to indemnify the Publisher in respect of all costs, damages, or other charges falling upon the newspaper as the result of legal actions or threatened legal actions arising from the publication of the advertisement, or any one or more of the series of advertisements, published in accordance with the copy instructions supplied to the newspaper in pursuance of the Advertiser's/Advertising Agency's order. In any case where a claim is made against the newspaper or the newspaper is used in litigation the Advertiser/Advertising Agency may ultimately be liable under the terms hereof, notice in writing shall be given to the Advertiser/Advertising Agency, and consultation shall take place before any expense is incurred or the claim is settled or the case is defended or otherwise disposed of.<br>&nbsp;<br></li>
		<li>Copy must be supplied without application from the Publisher. In the event of copy instructions not being received by the copy date the Publisher reserves the right to repeat copy last used. The Publisher cannot accept changes in dates of insertion unless these are confirmed in writing in time for the change to be made. The Publisher reserves the right to charge for any additional expense involved in such changes.<br>&nbsp;<br></li>
		<li>The placing of an order for the insertion of an advertisement shall amount to an acceptance of the above conditions and any conditions stipulated on an Agency's order form or elsewhere by an Agency or an Advertiser shall be void insofar as they are in conflict with them.<br>&nbsp;<br></li>
		<li>Lineage advertisements must be pre-paid and refunds on cancellation are not given.<br>&nbsp;<br></li>
		<li>The Business Advertisement (Disclosure) Order 1977 requires all advertisements by people who seek to sell goods, in the course of a business, to make that fact clear. It is the responsibility of the advertiser to comply with the above Order by using the word TRADE or Capital 'T'.<br>&nbsp;<br></li>
		<li>Marriage and Engagement Notices must be authorised in writing by both parties to the Engagement or Marriage.<br>&nbsp;<br></li>
		<li>Friday-Ad reserves the right to publish advertisements in any of it's other publications.<br>&nbsp;<br></li>
		<li>Refunds cannot be given on cancellations of advertisements which have already been published in any of our publications or on our Internet Web site.<br>&nbsp;<br></li>
		<li>Please note that telephone calls to and from Friday-Ad are monitored and recorded. Recordings may be accessed for training, security and dispute resolution purposes.<br>&nbsp;<br></li>
		<li>We reserve the right to apply Privacy numbers to any free ad we print in our printed publications or on any Friday Media Group websites.
        To help give our advertisers additional response, here at Friday-Ad, we randomly select some free online ads to be published in our Friday-Ad printed editions. When free ads appear in print, they will automatically have a privacy number assigned to them to protect your privacy.</li>
        </ol>
		<br>
		PUBLISHED BY
		<br>
		Friday-Ad Limited, Registered Office address: London Road, Sayers Common, West Sussex, BN6 9HS, Registered in England and Wales, Company Registration Number: 02311783
		<br><br>
		The Publishers of Friday-Ad cannot accept responsibility for monies sent to advertisers in response to mail order advertisements. Readers would be advised to check the authenticity of an advertiser before parting with money.
		<br>
		<br>
		<b>Friday-Ad.co.uk terms of use</b>
		<br><br>
		Access to and use of this site ('Friday-Ad.co.uk') is provided by Friday-Ad Group Ltd subject to the following terms:
		<br><br>
		By using Friday-Ad.co.uk you agree to be legally bound by these terms, which shall take effect immediately on your first use of Friday-Ad.co.uk.  If you do not agree to be legally bound by all the following terms please do not access and/or use Friday-Ad.co.uk.
		<br><br>
		Friday-Ad Group Ltd may change these terms at any time by posting changes online. Please review these terms regularly to ensure you are aware of any changes made by Friday-Ad.  Your continued use of Friday-Ad.co.uk after changes are posted means you agree to be legally bound by these terms as updated and/or amended.
		<br><br>
		<b>Use of Friday-Ad.co.uk</b>
		<br><br>
		You may not copy, reproduce, re-publish, download, post, broadcast, transmit, make available to the public, or otherwise use Friday-Ad.co.uk content in any way except for your own personal, non-commercial use.  You also agree not to adapt, alter or create a derivative work from any Friday-Ad.co.uk content except for your own personal, non-commercial use.  Any other use of Friday-Ad content requires the prior written permission of Friday-Ad Group Ltd.
		<br><br>
		You agree to use Friday-Ad.co.uk only for lawful purposes, and in a way that does not infringe the rights of, restrict or inhibit anyone else's use of Friday-Ad.co.uk.  Prohibited behaviour includes harassing, causing distress or inconvenience to any person, transmitting obscene or offensive content or disrupting the normal flow of dialogue within Friday-Ad.co.uk.
		<br><br>
		<b>Disclaimers and Limitation of Liability</b>
		<br><br>
		Friday-Ad.co.uk content, including the information, names, images, pictures, logos and icons regarding or relating to Friday-Ad Group Ltd and/or Friday-Ad.co.uk, its products and services (or to third party products and services), is provided "AS IS" and on an "IS AVAILABLE" basis without any representations or any kind of warranty made (whether express or implied by law) to the extent permitted by law, including the implied warranties of satisfactory quality, fitness for a particular purpose, non-infringement, compatibility, security and accuracy.
		<br>
		Under no circumstances will Friday-Ad Group and/or Friday-Ad.co.uk be liable for any of the following losses or damage (whether such losses where foreseen, foreseeable, known or otherwise): (a) loss of data; (b) loss of revenue or anticipated profits; (c) loss of business; (d) loss of opportunity; (e) loss of goodwill or injury to reputation; (f) losses suffered by third parties; or (g) any indirect, consequential, special or exemplary damages arising from the use of Friday-Ad.co.uk regardless of the form of action.
		<br><br>
		Friday-Ad Group Ltd does not warrant that functions contained in Friday-Ad.co.uk content will be uninterrupted or error free, that defects will be corrected, or that Friday-Ad.co.uk or the server that makes it available are free of viruses or bugs.
		<br><br>
		<b>Intellectual Property</b>
		<br><br>
		The names, images and logos identifying Friday-Ad Group Ltd, Friday-Ad.co.uk or third parties and their products and services are subject to copyright, design rights and trade marks of Friday-Ad Group Ltd and/or third parties.  Nothing contained in these terms shall be construed as conferring by implication, estoppel or otherwise any licence or right to use any trademark, patent, design right or copyright of Friday-Ad Group Ltd, Friday-Ad.co.uk, or any other third party.
		<br><br>
		<b>Contributions to Friday-Ad.co.uk</b>
		<br><br>
		Where you are invited to submit any contribution to Friday-Ad.co.uk (including any text, photographs, graphics, video or audio) you agree, by submitting your contribution, to grant Friday-Ad Group Ltd a perpetual, royalty-free, non-exclusive, sub-licensable right and license to use, reproduce, modify, adapt, publish, translate, create derivative works from, distribute, perform, play, make available to the public, and exercise all copyright and publicity rights with respect to your contribution worldwide and/or to incorporate your contribution in other works in any media now known or later developed for the full term of any rights that may exist in your contribution, and in accordance with privacy restrictions set out in the Friday-Ad Privacy Policy.  If you do not want to grant to Friday-Ad Group Ltd the rights set out above, please do not submit your contribution to Friday-Ad.co.uk.
		<br><br>
		Further to the above paragraph, by submitting your contribution to Friday-Ad.co.uk, you:
		<br><br>
		<b>i)	warrant that your contribution;</b>
		<br>
		<b>ii)	is your own original work and that you have the right to make it available to Friday-Ad Group for all the purposes specified above;</b>
		<br>
		<b>iii)	is not defamatory; and</b>
		<br>
		<b>iv)	does not infringe any law; and</b>
		<br>
		<b>v)	indemnify Friday-Ad Group Ltd against all legal fees, damages and other expenses that may be incurred by Friday-Ad Group Ltd as a result of your breach of the above warranty; and</b>
		<br>
		<b>vi)	waive any moral rights in your contribution for the purposes of its submission to and publication on Friday-Ad.co.uk and the purposes specified above.</b>
		<br><br>
		<b>Friday-Ad.co.uk Submission Rules</b>
		<br><br>
		You may not submit any defamatory or illegal material of any nature to Friday-Ad via the Place An Ad facility or otherwise.  This includes text, graphics, video, programs or audio.
		<br>
		Contributing material with the intention of committing or promoting an illegal act is strictly prohibited.
		<br>
		You agree to only submit materials which are your own original work. You must not violate, plagiarise, or infringe the rights of third parties including copyright, trademark, trade secrets, privacy, publicity, personal or proprietary rights.
		<br>
		If you post or send offensive or inappropriate content anywhere on or to Friday-Ad.co.uk or otherwise engage in any disruptive behaviour on Friday-Ad.co.uk, and Friday-Ad Group Ltd considers such behaviour to be serious and/or repeated, Friday-Ad Group Ltd may use whatever information that is available to it about you to stop any further such infringements. This may include informing relevant third parties such as your employer, school or email provider about the infringement/s.
		<br>
		Friday-Ad Group Ltd reserves the right to delete any contribution, or take action against any advertiser, at any time, for any reason.
		<br><br>
		<b>General</b>
		<br><br>
		If there is any conflict between these terms and specific terms appearing elsewhere on any Friday-Ad Group information, then the latter shall prevail.
		<br>
		If any of these terms are determined to be illegal, invalid or otherwise unenforceable by reason of the laws of any state or country in which these terms are intended to be effective, then to the extent and within the jurisdiction in which that term is illegal, invalid or unenforceable, it shall be severed and deleted from these terms and the remaining terms shall survive, remain in full force and effect and continue to be binding and enforceable.
		<br>
		These terms shall be governed by and interpreted in accordance with the laws of England and Wales.
		<br>
		<br>Friday-Ad Group Ltd
		<br>London Road
		<br>Sayers Common
		<br>West Sussex
		<br>BN6 9HS
		<br>
		<br>
EOD;
        $staticPage2 = new StaticPage();
        $staticPage2->setId($staticPageId);
        $staticPage2->setTitle("Terms and Conditions");
        $staticPage2->setStatus('1');
        $staticPage2->setDescription($description);
        $staticPage2->setSlug('terms-and-conditions');
        $staticPage2->setType(StaticPageRepository::STATIC_PAGE_TYPE_ID);
        $em->persist($staticPage2);
        $em->flush();
        $staticPageId++;


        $description = <<<EOD
        Help
EOD;

        $staticPage2 = new StaticPage();
        $staticPage2->setId($staticPageId);
        $staticPage2->setTitle("Help");
        $staticPage2->setStatus('1');
        $staticPage2->setDescription($description);
        $staticPage2->setSlug('help');
        $staticPage2->setType(StaticPageRepository::STATIC_PAGE_TYPE_ID);
        $em->persist($staticPage2);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        <p><strong>Environmental Activities</strong></p>
<p><br />Friday-Ad Group distribute a total of over one million copies every week. The printing is all done in-house on our printing press which is located at Friday-Ad's Head Office at Sayers Common.</p>
<p>All employees regularly recycle paper waste - there are green recycling bins available in every office. In addition to this, our raw printing process materials (paper and aluminium plates) are recycled.</p>
<p>All Friday-Ad editions are printed on 50% recycled paper<br />Aluminium plates used in the printing process are 100% recycled</p>
<p><br />We can all do more to protect the planet and its natural resources. Keep our environment clean, remember to recycle!</p>
EOD;

        $staticPage2 = new StaticPage();
        $staticPage2->setId($staticPageId);
        $staticPage2->setTitle("Recycling");
        $staticPage2->setStatus('1');
        $staticPage2->setDescription($description);
        $staticPage2->setSlug('recycling');
        $staticPage2->setType(StaticPageRepository::STATIC_PAGE_TYPE_ID);
        $em->persist($staticPage2);
        $em->flush();
        $staticPageId++;


        $description = <<<EOD
        <p><strong>Friday-Ad Cookies Policy</strong> <br /><br /> Our website uses cookies to help you get the most out of using our site. <br /><br /> <strong>How do cookies benefit you?</strong> <br /><br /> Cookies allow us to record information about your online preferences. This enables us to: &middot; Keep you logged in while you use our site. If you disable all cookies, you will have to enter your password more frequently during a session. &middot; Provide you with interesting information that is targeted to your interests. &middot; Spot any irregular activity in your account (i.e. if your account has been hacked into) and help keep your account secure. &middot; Offer you certain features of our site that are only available through the use of cookies, for example, shopping baskets. &middot; Analyse our site traffic and the way users use our site. This in turn enables us to consider and implement improvements to our site based on what you, the user, like and don&rsquo;t like. <br /><br /> <strong>What is a cookie?</strong> <br /><br /> Cookies are small text files sent from our website to your browser, which are then stored on the hard drive of your computer. Cookies record information about your online preferences. Our site uses cookies (both persistent/permanent and session), web beacons and third-party cookies to help enhance your experience of using the site. <br /><br /> <strong>Cookies that we use on our site:</strong> <br /><br /> <strong>Session cookies:</strong> These cookies are automatically deleted once your session has ended. Your session on our website ends when you log out or close your browser. <br /><br /> <strong>Persistent cookies:</strong> Persistent or permanent cookies are not deleted when you end your session on our website. We use these cookies to remember the preferences you have chosen in previous sessions. <br /><br /> <strong>Web beacons:</strong> These work in much of the same way as cookies but instead of being a small text file, web beacons are a small electronic image put in the web page. We use web beacons to anonymously track traffic patterns of users from page to page within our website to help with site improvements. <br /><br /> <strong>Flash cookies:</strong> Most computers have Adobe Flash installed on them. Flash may store small files that are used in the same way as regular cookies on your computer. These are known as &lsquo;Local Shared Objects&rsquo; or Flash cookies. <br /><br /> <strong>Third party cookies:</strong> We may work with other approved companies and business partners (&lsquo;third parties&rsquo;) who place their own cookies or web beacons on our site. They work in much the same way as our own cookies but are not actually placed by us. Any third parties that do place cookies on our website are required to collect information in a specified manner, i.e. not for their own purposes. <br /><br /> Any website can send its own cookies to your browser, if your browser&rsquo;s preferences allow it. However, to protect your privacy, your browser will only permit a website to access the cookies that site has already sent to you, not the cookies sent to you by other sites. <br /><br /> <strong>Can cookies damage my computer?</strong> <br /><br /> No, cookies cannot damage your computer. They are not computer programs and cannot be used to spread viruses to your machine. Although cookies are stored on your computer&rsquo;s hard drive, they cannot read other information saved on that hard drive. They also cannot get a user&rsquo;s email addresses or other personal information not disclosed on our site. Cookies only contain and transfer as much information as you have disclosed on our site. <br /><br /> <strong>How can you manage cookies?</strong> <br /><br /> As a user, you are able to manage cookies and choose whether your computer/browser accepts cookies or not. As each browser is different, the way in which you manage cookies differs from browser to browser. Take a look at the &lsquo;Help&rsquo; menu of your browser and search for &lsquo;cookies&rsquo;. You should be able to find instructions for managing your cookies. To find out how to manage Flash cookies, visit the Flash Player help <a href="http://www.macromedia.com/support/documentation/en/flashplayer/help/settings_manager07.html">http://www.macromedia.com/support/documentation/en/flashplayer/help/settings_manager07.html</a> website. <br /><br /> <strong>User Choices for opting out from Third Party Data collection</strong> <br /> <br /> Online users can opt-out from Third Party preference data collection, for Online Behavioral Advertising, using any one of the following methods by: <br /> 1. Using the Online Choices opt-out tool provided at <a href="http://www.youronlinechoices.com/opt-out" target="_blank">http://www.youronlinechoices.com/opt-out</a> the user can select the company(s) to stop them from collecting Online Behavioral Advertising data. <br /> 2. Changing the computer&rsquo;s browser settings to block delete and/or control the use of all third party cookies. Refer to the computer&rsquo;s web browser&rsquo;s Help documentation for more information about managing the use of cookies directly through the browser settings. <br /> Online users have the choice of opting out via the &ldquo;Your Online Choices website&rdquo; or by managing browser cookies. The Opt-out tool downloads a browser cookie and will only work if the user&rsquo;s browser is set to accept third-party cookies. The opt-out tool and cookie management via the browsers is specific to each computer and each version and each type of browser. If an opt-out cookie is deleted from the browser&rsquo;s cookie files or if a different computer, browser version and/or browser type is used, the user will need to repeat the opt-out process. <br /><br /> For more information about cookies, what they are, what they do, and how to manage them, visit <a href="http://www.aboutcookies.org">www.aboutcookies.org</a>. <br /> <strong>Remember: Cookies help you get the best out of our website. Disabling all cookies will prevent our website functioning as well as it should for you.</strong></p>
EOD;

        $staticPage2 = new StaticPage();
        $staticPage2->setId($staticPageId);
        $staticPage2->setTitle("Cookies Policy");
        $staticPage2->setStatus('1');
        $staticPage2->setDescription($description);
        $staticPage2->setSlug('cookies-policy');
        $staticPage2->setType(StaticPageRepository::STATIC_PAGE_TYPE_ID);
        $em->persist($staticPage2);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        <h2><strong>Where to find us </strong></h2>
<h3><strong>Head Office address and contact details</strong></h3>
<p><br /><strong>Friday-Ad Limited</strong><br />Registered Office address:<br />London Road<br />Sayers Common<br />West Sussex<br />BN6 9HS<br />Registered in England and Wales<br />Company Registration Number: 02311783<br /> <br />Telephone: 01273 837700</p>
<p>&nbsp;</p>
<p><strong>Contact Telephone Numbers</strong>&nbsp;</p>
<p>&nbsp;</p>
<table style="height: 440px;" width="601">
<tbody>
<tr>
<td>Adline</td>
<td>Tel: 0844 871 6600</td>
</tr>
<tr>
<td>(for booking classified adverts, private and trade)</td>
<td>Fax: 0844 871 6613</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Adline</td>
<td>Tel: 0844 871 6604</td>
</tr>
<tr>
<td>(for booking business display adverts)</td>
<td>Fax: 0844 871 6607</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Recruitment Advertising</td>
<td>Tel: 0844 871 6605</td>
</tr>
<tr>
<td>(for display adverts)</td>
<td>Fax: 0844 871 6606</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>Email: recruitment@friday-ad.co.uk</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Recruitment Advertising</td>
<td>Tel: 0844 871 6605</td>
</tr>
<tr>
<td>(for classified adverts)</td>
<td>Fax: 0844 871 6606</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>Email: recruitment@friday-ad.co.uk</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Distribution</td>
<td>Tel: 0844 871 6612</td>
</tr>
<tr>
<td>(to become a stocklist)</td>
<td>Fax: 01273 837781</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>Email: distribution@friday-ad.co.uk</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Customer Services</td>
<td>Tel: 0844 871 6604</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>Fax: 0844 871 6614</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>Email: customerservices@friday-ad.co.uk</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
<tr>
<td>Internet Support</td>
<td>Tel: 0844 871 0772</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>Email: support@friday-ad.co.uk</td>
</tr>
<tr>
<td>&nbsp;</td>
<td>&nbsp;</td>
</tr>
</tbody>
</table>
<p><strong>Feedback/Comments/Queries Email forms</strong></p>
<p>Friday-Ad strives to supply its customers with content and special offers that will be of interest to them. However, without your input Friday-Ad wouldn't be the success that it is, therefore any comments, feedback, queries or ideas you may have regarding our service will be gratefully received.</p>
<p>Sales queries<br />Submit a question or comment regarding advertising with the Friday-Ad.</p>
<p>Technical queries<br />Submit a query about the website or to report errors or broken links.</p>
<p>Comments and Ideas<br />Send us your ideas on how we might improve our service or to tell us what you think of our current service.</p>
<p><strong>Jobs at Friday-Ad</strong> <br />If you are interested in working for the UK's leading classified publisher, please check here for our current vacancies.</p>
<p>&nbsp;</p>
EOD;

        $staticPage2 = new StaticPage();
        $staticPage2->setId($staticPageId);
        $staticPage2->setTitle("Contact us");
        $staticPage2->setStatus('1');
        $staticPage2->setDescription($description);
        $staticPage2->setSlug('contact-us');
        $staticPage2->setType(StaticPageRepository::STATIC_PAGE_TYPE_ID);
        $em->persist($staticPage2);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
<b>Privacy Policy</b>
    <br>
    Friday-Ad is committed to protecting your right to privacy as a consumer of our online services.
    It is our policy to respect the privacy of private communication and the information you provide to us will be held on Friday-Ad computers that are based in the UK.
    <br>
    We collect information about our users in order to help us continually improve the service we offer and so that we can enter into commercial arrangements, including the sale of advertising space, to provide more for our customers. Friday-Ad will always adhere to the UK Data Protection Legislation.
    <br>
    This Privacy Policy only relates to the Friday-Ad service and does not extend to your use of the Internet outside of the Friday-Ad site.
    <br><br>
    <b>Cookies</b>
    <br>
    Please see our <a href="/cookies-policy">Cookies policy</a> to find out about cookies and which ones are used on Friday-Ad.co.uk in order to help you receive a good service. To check your own cookie and privacy settings, check the ‘Help’ menu of your browser.
    <br><br>
    <b>What data do we collect?</b>
    <br>
    We will collect personal data as provided to us during the registration process, which you agree to supply us as accurate. We will also collect personal data from other parts of the Friday-Ad site as required, for example, when customers enter competitions. We do not monitor your use of the Internet but we do use cookie technology to monitor your use of the Friday-Ad service. This information is not stored alongside your personal data and will only be used on an anonymous, aggregated basis. We may hold personal data relating to the transactions you enter into with Friday-Ad or others through Friday-Ad such as online retailers. We may also ask for other data so that we can obtain a better understanding of our users' requirements. This information will help us to sell appropriate advertising space and provide better personalisation services for our users.
    <br><br>
    <b>What do we do with the data we collect?</b>
    <br>
    We may use personal information about you to build up a profile of your interests and preferences. When you supply us with your details there will be a box to tick if you do not wish to receive email newsletters or other information which may be of interest to you. This information will not be disclosed to third parties.
    <br><br>
    <b>Users aged 16 and under</b>
    <br>
    If you are aged 16 or under, please get your parent/guardian's permission beforehand whenever you provide personal information on any Friday-Ad website. Users without this consent are not allowed to provide us with personal information.
    <br><br>
    <b>Changing your contact details</b>
    <br>
    You can update or amend your contact details at any time by using your My Account page which can be located on the Friday-Ad website. (Please note: You must be logged in to change your details)
    <br><br>
    <b>Changes to the Policy</b>
    <br>
    If we do change or amend our privacy policy we will notify users via the Friday-Ad website. The Privacy Policy will always be easily accessible online throughout all of the Friday-Ad services.
    <br>
    Advertisement copy shall be legal, decent, honest and truthful; shall comply with the British Code of Advertising Practice and all other Codes under the general supervision of the Advertising Standards Authority: and shall comply with the requirements of current legislation.
EOD;
        $staticPage3 = new StaticPage();
        $staticPage3->setId($staticPageId);
        $staticPage3->setTitle("Privacy Policy");
        $staticPage3->setDescription($description);
        $staticPage3->setStatus('1');
        $staticPage3->setSlug('privacy-policy');
        $staticPage3->setType(StaticPageRepository::STATIC_PAGE_TYPE_ID);
        $em->persist($staticPage3);
        $em->flush();
        $staticPageId++;

        $staticPage4 = new StaticPage();
        $staticPage4->setId($staticPageId);
        $staticPage4->setTitle("What are you <b>selling</b>?");
        $staticPage4->setName("PAA step 1 help for find category.");
        $staticPage4->setDescription('Type one or two words to describe your advert. OR  Can not find the category by searching? Search again or choose from a list.');
        $staticPage4->setSlug('paa-first-step');
        $staticPage4->setStatus('1');
        $staticPage4->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage4);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        <b>Make it easy to find</b>: A good title is
        really important - try to include as
        much relevant information as possible,
        it will make your advert easier to find
        on both Friday-Ad and Google.<br /><br />

        <b>Be descriptive</b>: Use your description
        to really let potential buyers know all
        about the item. Make it informative anc
        friendly. Tell them why it's great and
        any things that might be useful in
        deciding to buy your item<br /><br />

        <b>Be detailed</b>: Providing details like
        model numbers, any notable features
        or markings and details of what is
        included will help buyers choose your
        item and reduce the number of
        questions asked<br /><br />

        <b>Price</b>: Be realistic. if you overprice it, it
        will deter buyers from looking at your
        ad. Have a look around to see what
        other similar items are being sold for. it
        will give you an idea of what a reasonable price is.
EOD;
        $staticPage5 = new StaticPage();
        $staticPage5->setId($staticPageId);
        $staticPage5->setTitle("Sell your item <b>quickly!</b>");
        $staticPage5->setName("PAA for sale step 2 help describe item and choose price.");
        $staticPage5->setDescription($description);
        $staticPage5->setStatus('1');
        $staticPage5->setSlug('paa-for-sale-second-step');
        $staticPage5->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage5);
        $em->flush();
        $staticPageId++;

        $staticPage6 = new StaticPage();
        $staticPage6->setId($staticPageId);
        $staticPage6->setTitle("Friday Ad <b>Login</b>");
        $staticPage6->setName("PAA step 3 help for non logged in user.");
        $staticPage6->setDescription('Login help goes here');
        $staticPage6->setStatus('1');
        $staticPage6->setSlug('paa-login-third-step');
        $staticPage6->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage6);
        $em->flush();
        $staticPageId++;

        $staticPage7 = new StaticPage();
        $staticPage7->setId($staticPageId);
        $staticPage7->setTitle("Friday Ad <b>Register</b>");
        $staticPage7->setName("PAA step 3 help for non registered user.");
        $staticPage7->setDescription('Register help goes here');
        $staticPage7->setStatus('1');
        $staticPage7->setSlug('paa-registration-third-step');
        $staticPage7->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage7);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        Adding more detail and some photos
to your advert gives your buyers more
confidence in your car and will mean
you spend less time answering
potential buyers questions.<br /><br />

Photo's are really important to your
adverts success. So much so. that we
don't put adverts online without at least
one!<br />
<a href="javascript:void(0)" id="paa_step4_add_your_photos">Add your photos now</a>
EOD;
        $staticPage8 = new StaticPage();
        $staticPage8->setId($staticPageId);
        $staticPage8->setTitle("It's all in the <b>details</b>");
        $staticPage8->setName("PAA for sale step 4 help for details.");
        $staticPage8->setDescription($description);
        $staticPage8->setStatus('1');
        $staticPage8->setSlug('paa-for-sale-fourth-step');
        $staticPage8->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage8);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        <b>What's This</b> <p>Friday-Ad now offer, at no charge, an extra security service when receiving phone calls from people responding to your advert. Friday-Ad keeps your original phone number private but still allows potential buyers to contact you directly. We believe people value keeping this privacy.</p> <b>How does it work?</b> <p>Your phone number will be replaced by an 07044, 07092 or 07052 number when your advert is published. When a potential buyer calls this number the call is automatically redirected to your private number. Your private number always remains confidential.</p> <b>How much does the call cost the caller?</b> <p>At Friday-Ad we place many adverts free of charge, in order to continue to do so we make a small amount of revenue from the 070 service that we now offer. This has also been universally welcomed for the privacy that it now offers our Private advertisers. Calls to privacy numbers are charged at 37.5p per minute at peak times, falling to 25p per minute in the evenings and just 12.5p per minute at the weekends (BT landline customers). If you are not with BT, calls will be charged at your own operators rate. Calls from mobiles will vary depending on the rate charged by your operator.</p> <p>To help give our advertisers additional response, here at Friday-Ad, we randomly select some free online ads to be published in our Friday-Ad printed editions. When free ads appear in print, they will automatically have a privacy number assigned to them to protect your privacy.</p>
EOD;
        $staticPage9 = new StaticPage();
        $staticPage9->setId($staticPageId);
        $staticPage9->setTitle("Privacy Number");
        $staticPage9->setName("PAA step 3 help for non registered user's privacy number.");
        $staticPage9->setDescription($description);
        $staticPage9->setStatus('1');
        $staticPage9->setSlug('privacy-number-block');
        $staticPage9->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage9);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
Image upload help here
EOD;
        $staticPage10 = new StaticPage();
        $staticPage10->setId($staticPageId);
        $staticPage10->setTitle("Image upload help");
        $staticPage10->setName("PAA step 4 help for image help.");
        $staticPage10->setDescription($description);
        $staticPage10->setStatus('1');
        $staticPage10->setSlug('paa-image-upload-help');
        $staticPage10->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage10);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        <b>Make it easy to find</b>: A good title is
        really important - try to include as
        much relevant information as possible,
        it will make your advert easier to find
        on both Friday-Ad and Google.<br /><br />

        <b>Be descriptive</b>: Use your description
        to really let potential buyers know all
        about the item. Make it informative anc
        friendly. Tell them why it's great and
        any things that might be useful in
        deciding to buy your item<br /><br />

        <b>Be detailed</b>: Providing details like
        model numbers, any notable features
        or markings and details of what is
        included will help buyers choose your
        item and reduce the number of
        questions asked<br /><br />

        <b>Price</b>: Be realistic. if you overprice it, it
        will deter buyers from looking at your
        ad. Have a look around to see what
        other similar items are being sold for. it
        will give you an idea of what a reasonable price is.
EOD;

        $staticPage11 = new StaticPage();
        $staticPage11->setId($staticPageId);
        $staticPage11->setTitle("Sell your item <b>quickly!</b>");
        $staticPage11->setName("PAA animals step 2 help describe item and choose price.");
        $staticPage11->setDescription($description);
        $staticPage11->setStatus('1');
        $staticPage11->setSlug('paa-animals-second-step');
        $staticPage11->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage11);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        Adding more detail and some photos
to your advert gives your buyers more
confidence in your car and will mean
you spend less time answering
potential buyers questions.<br /><br />

Photo's are really important to your
adverts success. So much so. that we
don't put adverts online without at least
one!<br />
<a href="javascript:void(0)" id="paa_step4_add_your_photos">Add your photos now</a>
EOD;

        $staticPage12 = new StaticPage();
        $staticPage12->setId($staticPageId);
        $staticPage12->setTitle("It's all in the <b>details</b>");
        $staticPage12->setName("PAA animals step 4 help for details.");
        $staticPage12->setDescription($description);
        $staticPage12->setStatus('1');
        $staticPage12->setSlug('paa-animals-fourth-step');
        $staticPage12->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage12);
        $em->flush();
        $staticPageId++;

        $staticPage13 = new StaticPage();
        $staticPage13->setId($staticPageId);
        $staticPage13->setTitle("Sell your item <b>quickly!</b>");
        $staticPage13->setName("PAA jobs step 2 help describe item and choose price.");
        $staticPage13->setDescription($description);
        $staticPage13->setStatus('1');
        $staticPage13->setSlug('paa-jobs-second-step');
        $staticPage13->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage13);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        Adding more detail and some photos
to your advert gives your buyers more
confidence in your car and will mean
you spend less time answering
potential buyers questions.<br /><br />

Photo's are really important to your
adverts success. So much so. that we
don't put adverts online without at least
one!<br />
<a href="javascript:void(0)" id="paa_step4_add_your_photos">Add your photos now</a>
EOD;

        $staticPage14 = new StaticPage();
        $staticPage14->setId($staticPageId);
        $staticPage14->setTitle("It's all in the <b>details</b>");
        $staticPage14->setName("PAA jobs step 4 help for details.");
        $staticPage14->setDescription($description);
        $staticPage14->setStatus('1');
        $staticPage14->setSlug('paa-jobs-fourth-step');
        $staticPage14->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage14);
        $em->flush();
        $staticPageId++;

        $staticPage15 = new StaticPage();
        $staticPage15->setId($staticPageId);
        $staticPage15->setTitle("Sell your item <b>quickly!</b>");
        $staticPage15->setName("PAA community step 2 help describe item and choose price.");
        $staticPage15->setDescription($description);
        $staticPage15->setStatus('1');
        $staticPage15->setSlug('paa-community-second-step');
        $staticPage15->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage15);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        Adding more detail and some photos
to your advert gives your buyers more
confidence in your car and will mean
you spend less time answering
potential buyers questions.<br /><br />

Photo's are really important to your
adverts success. So much so. that we
don't put adverts online without at least
one!<br />
<a href="javascript:void(0)" id="paa_step4_add_your_photos">Add your photos now</a>
EOD;

        $staticPage16 = new StaticPage();
        $staticPage16->setId($staticPageId);
        $staticPage16->setTitle("It's all in the <b>details</b>");
        $staticPage16->setName("PAA community step 4 help for details.");
        $staticPage16->setDescription($description);
        $staticPage16->setStatus('1');
        $staticPage16->setSlug('paa-community-fourth-step');
        $staticPage16->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage16);
        $em->flush();
        $staticPageId++;

        $staticPage17 = new StaticPage();
        $staticPage17->setId($staticPageId);
        $staticPage17->setTitle("Sell your item <b>quickly!</b>");
        $staticPage17->setName("PAA motors step 2 help describe item and choose price.");
        $staticPage17->setDescription($description);
        $staticPage17->setStatus('1');
        $staticPage17->setSlug('paa-motors-second-step');
        $staticPage17->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage17);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        Adding more detail and some photos
to your advert gives your buyers more
confidence in your car and will mean
you spend less time answering
potential buyers questions.<br /><br />

Photo's are really important to your
adverts success. So much so. that we
don't put adverts online without at least
one!<br />
<a href="javascript:void(0)" id="paa_step4_add_your_photos">Add your photos now</a>
EOD;

        $staticPage18 = new StaticPage();
        $staticPage18->setId($staticPageId);
        $staticPage18->setTitle("It's all in the <b>details</b>");
        $staticPage18->setName("PAA motors step 4 help for details.");
        $staticPage18->setDescription($description);
        $staticPage18->setStatus('1');
        $staticPage18->setSlug('paa-motors-fourth-step');
        $staticPage18->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage18);
        $em->flush();
        $staticPageId++;

        $staticPage19 = new StaticPage();
        $staticPage19->setId($staticPageId);
        $staticPage19->setTitle("Registration / login help");
        $staticPage19->setName("Registration / login help.");
        $staticPage19->setDescription('Registration / login help here');
        $staticPage19->setStatus('1');
        $staticPage19->setSlug('registration-login-help');
        $staticPage19->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage19);
        $em->flush();
        $staticPageId++;

        /* TODO:: This need to be add at live time for while we will use ga code for test account
        $description = <<<EOD
        <script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function()
{ (i[r].q=i[r].q||[]).push(arguments)}
,i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
ga('create', 'UA-217484-2', 'auto');
ga('require', 'displayfeatures');
ga('send', 'pageview');
</script>
EOD;
*/

        /* Janak's url
        $description = <<<EOD
        <script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-60833554-1', 'auto');
  ga('send', 'pageview');

</script>
EOD;
*/

        $description = <<<EOD
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
    (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
    m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-61004578-1', 'auto');
ga('send', 'pageview');

</script>
EOD;
        $staticPage18 = new StaticPage();
        $staticPage18->setId($staticPageId);
        $staticPage18->setTitle("Google Analytics code: All page");
        $staticPage18->setName("All page Google Analytics code");
        $staticPage18->setDescription($description);
        $staticPage18->setStatus('1');
        $staticPage18->setSlug('all-page-google-analytics-code');
        $staticPage18->setType(StaticPageRepository::STATIC_BLOCK_GA_CODE_ID);
        $em->persist($staticPage18);
        $em->flush();
        $staticPageId++;


        $description = <<<EOD
        <script>
        /* Missing code */
        </script>
EOD;

        $staticPage18 = new StaticPage();
        $staticPage18->setId($staticPageId);
        $staticPage18->setTitle("Google Analytics: PAA Completion Page");
        $staticPage18->setName("Google Analytics: PAA Completion Page");
        $staticPage18->setDescription($description);
        $staticPage18->setStatus('1');
        $staticPage18->setSlug('paa-completion-google-analytics-code');
        $staticPage18->setType(StaticPageRepository::STATIC_BLOCK_GA_CODE_ID);
        $em->persist($staticPage18);
        $em->flush();
        $staticPageId++;

        $staticPage19 = new StaticPage();
        $staticPage19->setId($staticPageId);
        $staticPage19->setTitle("Sell your item <b>quickly!</b>");
        $staticPage19->setName("PAA property step 2 help describe item and choose price.");
        $staticPage19->setDescription($description);
        $staticPage19->setStatus('1');
        $staticPage19->setSlug('paa-property-second-step');
        $staticPage19->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage19);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        Adding more detail and some photos
to your advert gives your buyers more
confidence in your car and will mean
you spend less time answering
potential buyers questions.<br /><br />

Photo's are really important to your
adverts success. So much so. that we
don't put adverts online without at least
one!<br />
<a href="javascript:void(0)" id="paa_step4_add_your_photos">Add your photos now</a>
EOD;

        $staticPage20 = new StaticPage();
        $staticPage20->setId($staticPageId);
        $staticPage20->setTitle("It's all in the <b>details</b>");
        $staticPage20->setName("PAA property step 4 help for details.");
        $staticPage20->setDescription($description);
        $staticPage20->setStatus('1');
        $staticPage20->setSlug('paa-property-fourth-step');
        $staticPage20->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage20);
        $em->flush();
        $staticPageId++;

        $staticPage21 = new StaticPage();
        $staticPage21->setId($staticPageId);
        $staticPage21->setTitle("Sell your item <b>quickly!</b>");
        $staticPage21->setName("PAA services step 2 help describe item and choose price.");
        $staticPage21->setDescription($description);
        $staticPage21->setStatus('1');
        $staticPage21->setSlug('paa-services-second-step');
        $staticPage21->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage21);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        Adding more detail and some photos
to your advert gives your buyers more
confidence in your car and will mean
you spend less time answering
potential buyers questions.<br /><br />

Photo's are really important to your
adverts success. So much so. that we
don't put adverts online without at least
one!<br />
<a href="javascript:void(0)" id="paa_step4_add_your_photos">Add your photos now</a>
EOD;

        $staticPage22 = new StaticPage();
        $staticPage22->setId($staticPageId);
        $staticPage22->setTitle("It's all in the <b>details</b>");
        $staticPage22->setName("PAA services step 4 help for details.");
        $staticPage22->setDescription($description);
        $staticPage22->setStatus('1');
        $staticPage22->setSlug('paa-services-fourth-step');
        $staticPage22->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage22);
        $em->flush();
        $staticPageId++;

        $staticPage23 = new StaticPage();
        $staticPage23->setId($staticPageId);
        $staticPage23->setTitle("Sell your item <b>quickly!</b>");
        $staticPage23->setName("PAA adult step 2 help describe item and choose price.");
        $staticPage23->setDescription($description);
        $staticPage23->setStatus('1');
        $staticPage23->setSlug('paa-adult-second-step');
        $staticPage23->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage23);
        $em->flush();
        $staticPageId++;

        $description = <<<EOD
        Adding more detail and some photos
to your advert gives your buyers more
confidence in your car and will mean
you spend less time answering
potential buyers questions.<br /><br />

Photo's are really important to your
adverts success. So much so. that we
don't put adverts online without at least
one!<br />
<a href="javascript:void(0)" id="paa_step4_add_your_photos">Add your photos now</a>
EOD;

        $staticPage24 = new StaticPage();
        $staticPage24->setId($staticPageId);
        $staticPage24->setTitle("It's all in the <b>details</b>");
        $staticPage24->setName("PAA adult step 4 help for details.");
        $staticPage24->setDescription($description);
        $staticPage24->setStatus('1');
        $staticPage24->setSlug('paa-adult-fourth-step');
        $staticPage24->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage24);
        $em->flush();
        $staticPageId++;

        $staticPage25 = new StaticPage();
        $staticPage25->setId($staticPageId);
        $staticPage25->setTitle("Add your business detail");
        $staticPage25->setName("PAA adult step 3 registration company information.");
        $staticPage25->setDescription($description);
        $staticPage25->setStatus('1');
        $staticPage25->setSlug('paa-business-details-step');
        $staticPage25->setType(StaticPageRepository::STATIC_BLOCK_TYPE_ID);
        $em->persist($staticPage25);
        $em->flush();
        $staticPageId++;
    }

    /**
     * Get order.
     *
     * @return integer
     */
    public function getOrder()
    {
        return 6; // the order in which fixtures will be loaded
    }
}
