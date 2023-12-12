<form method="post" id="payment-form">
    {{ csrf_field() }}

    <div class="form-group">
        <div>

            <div id="card-element" class="form-control">
            <!-- a Stripe Element will be inserted here. -->
            </div>

            <!-- Used to display Element errors -->
            <div id="card-errors" role="alert"></div>

        </div>
    </div>

    <button class="btn btn-primary btn-lg btn-block" type="submit">Add New Credit Card</button>

</form>



<script src="https://js.stripe.com/v3/"></script>

<script>
    var stripe = Stripe('{{ config('paymentProcessors.stripe.key') }}');
    var elements = stripe.elements({
        locale: '{!! \App\Models\Languages::current()->otherCodeStandard('IANA') !!}'
    });

    // Custom styling can be passed to options when creating an Element.
    var style = {
        base: {
            fontSize: '16px',
            color: "#32325d",
        }
    };

    // Create an instance of the card Element
    var card = elements.create('card', { 
        style: style
    });

    // Add an instance of the card Element into the `card-element` <div>
    card.mount('#card-element');

    card.addEventListener('change', function(event) {
        var displayError = document.getElementById('card-errors');
        if (event.error) {
            displayError.textContent = event.error.message;
        } else {
            displayError.textContent = '';
        }
    });

    // Create a token or display an error when the form is submitted.
    var form = document.getElementById('payment-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();

        stripe.createToken(card).then(function(result) {
            if (result.error) {
                // Inform the customer that there was an error
                var errorElement = document.getElementById('card-errors');
                errorElement.textContent = result.error.message;
            } else {
                // Send the token to your server
                stripeTokenHandler(result.token);
            }
        });
    });

    function stripeTokenHandler(token) {
        // Insert the token ID into the form so it gets submitted to the server
        var form = document.getElementById('payment-form');
        var hiddenInput = document.createElement('input');
        hiddenInput.setAttribute('type', 'hidden');
        hiddenInput.setAttribute('name', 'stripeNewCardToken');
        hiddenInput.setAttribute('value', token.id);
        form.appendChild(hiddenInput);
        form.submit();
    }

</script>