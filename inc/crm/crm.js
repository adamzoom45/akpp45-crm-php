/**
 * CRM JavaScript
 */

jQuery(document).ready(function($) {
    'use strict';
    
    // Mobile menu toggle
    $('#mobile-menu-toggle').on('click', function() {
        $('#mobile-menu').toggleClass('active');
        $(this).toggleClass('active');
    });
    
    // Smooth scroll for anchor links
    $('a[href^="#"]').on('click', function(e) {
        const href = $(this).attr('href');
        if (href === '#') return;
        
        const target = $(href);
        if (target.length) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: target.offset().top - 80
            }, 500);
        }
    });
    
    // Contact form
    $('#contact-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = $(this).serialize();
        const messageDiv = $('#form-message');
        
        $.post(akppKurgan.ajaxUrl, formData + '&action=akpp_contact_form&nonce=' + akppKurgan.nonce, function(response) {
            if (response.success) {
                messageDiv.css('color', '#00ff88').text(response.data.message);
                $('#contact-form')[0].reset();
            } else {
                messageDiv.css('color', '#ef4444').text(response.data.message);
            }
        });
    });
});