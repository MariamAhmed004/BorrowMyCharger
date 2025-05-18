  function toggleFaq(element) {
        // Get the parent faq-item
        const faqItem = element.parentNode;
        
        // Check if this faq-item is already active
        const isActive = faqItem.classList.contains('active');
        
        // Close all FAQ items first
        const allFaqItems = document.querySelectorAll('.faq-item');
        allFaqItems.forEach(item => {
            item.classList.remove('active');
        });
        
        // If the clicked item wasn't active before, make it active
        if (!isActive) {
            faqItem.classList.add('active');
        }
    }
    
    // Initialize the first FAQ item as open 
    document.addEventListener('DOMContentLoaded', function() {
       
    });