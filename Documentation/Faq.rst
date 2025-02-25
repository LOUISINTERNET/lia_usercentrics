:navigation-title: FAQ

..  _faq:

================================
Frequently Asked Questions (FAQ)
================================

..  accordion::
    :name: faq

    ..  accordion-item:: How can I install this extension?
        :name: installation
        :header-level: 2
        :show:

        See chapter :ref:`installation`.

    ..  accordion-item:: I'm getting an exception!
        :name: exception
        :header-level: 2

        ..  code-block::
            (1/1) #1741944668 LIA\LiaUsercentrics\Exceptions\LiaUsercentricsException
            The Usercentrics settings are not set.

        This error occurs when the necessary Usercentrics settings have not been properly configured in 
        your TYPO3 installation. The extension is unable to find the required TypoScript configuration 
        for Usercentrics, which is essential for managing cookie consent and other privacy settings.

    ..  accordion-item:: How to can I include the TypoScript?
        :name: configuration
        :header-level: 2
        You can including the appropriate static template in your TYPO3 setup. Here's how to do it:

        #. Go to the TYPO3 backend.
        #. Navigate to the :guilabel:`Site Management > TypoScript` module.
        #. Select the page where you want the Usercentrics settings to be applied (typically the root page of your site).
        #. Select `Edit TypoScript Record` and klick on :guilabel:`Edit the whole TypoScript record`
        #. In the "Advanced Options" tab, scroll down to find the list of static templates.
        #. Add the necessary static template for Usercentrics, "LIA Usercentrics (lia_usercentrics)".
        #. Save the changes.
        This will ensure that the required TypoScript settings are loaded and the extension can properly access the Usercentrics configuration.
        
        See also chapter :ref:`configuration`.

    ..  accordion-item:: Where to get help?
        :name: help
        :header-level: 2

        See chapter :ref:`help`.
