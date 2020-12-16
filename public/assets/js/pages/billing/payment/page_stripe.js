$(document).ready(function(){
    var stripe = Stripe('pk_test_TYooMQauvdEDq54NiTphI7jx');
    var elements = stripe.elements();
    var cardElement = elements.create('card');
    cardElement.mount('#card-element');
    
    var makeAuto = $("#makeAuto");
    var cardholderName = document.getElementById('name');
    var cardContainer = document.getElementById('stripe_container');
    var clientSecret = cardContainer.dataset.secret;
    var customerId = cardContainer.dataset.customer;

    $('#createStripePaymentMethodForm').submit(function(event) {
        
        event.preventDefault();
        const form = event.target;

        //Display loading spinna

        stripe.confirmCardSetup(
            clientSecret,
            {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: cardholderName.value,
                    },
                },
            }
        ).then(function(result) {
            if (result.error) {
                // Display error.message in your UI.
                console.error(result.error);
                $('#stripe_errors').text(result.error.message);
                
            } else {
                // The setup has succeeded. Display a success message.
                console.log(result);

                //Add hidden fields to form
                $('<input>').attr({
                    type: 'hidden',
                    id: 'customerId',
                    name: 'customerId',
                    value: customerId,
                }).appendTo('#createStripePaymentMethodForm');

                $('<input>').attr({
                    type: 'hidden',
                    id: 'token',
                    name: 'token',
                    value: result.setupIntent.payment_method,
                }).appendTo('#createStripePaymentMethodForm');
                
                $('<input>').attr({
                    type: 'hidden',
                    id: 'identifier',
                    name: 'identifier',
                    value: result.setupIntent.id,
                }).appendTo('#createStripePaymentMethodForm');

                //Submit form time
                form.submit();
            }
        });
    });

    // updatePaymentForm();

    $("#country").change(function(){
        updateSubdivisions();
    });

    makeAuto.change(function(){
        if (makeAuto.is(":checked")) {
            $("#autoPayDescription").show();
        }
        else {
            $("#autoPayDescription").hide();
        }
    });

    $("#payment_method").change(function(){
        updatePaymentForm();
    });

});

function updateSubdivisions()
{
    var country = $("#country").val();
    $("#state").prop('disabled',true);
    var jqxhr = $.get("/portal/billing/subdivisions/" + country, function(data) {
        $("#state").empty();
        var show = false;
        $.each(data.subdivisions, function (index, value) {
            show = true;
           $("#state").append("<option value='" + index + "'>" + value + "</option>");
        });
        if (show === true) {
            $("#stateWrapper").show();
        }
        else {
            $("#stateWrapper").hide();
        }
    })
    .fail(function() {
        swal({
            title: Lang.get("headers.error"),
            text: Lang.get("errors.failedToLookupSubdivision"),
            type: "error",
            showCancelButton: false
        },
        function(isConfirm) {
            if (isConfirm) {
                window.location.reload();
            }
        });
    })
    .always(function() {
        $("#state").prop('disabled',false);
    });
}

// function updatePaymentForm()
// {
//     var selectedPaymentMethod = $("#payment_method").val();
//     switch (selectedPaymentMethod) {
//         case "new_card":
//             $(".new_card").show();
//             $(".non_paypal").show();
//             $(".paypal").hide();
//             break;
//         case "paypal":
//             $(".new_card").hide();
//             $(".non_paypal").hide();
//             $(".paypal").show();
//             break;
//         default:
//             //Existing card
//             $(".new_card").hide();
//             $(".non_paypal").show();
//             $(".paypal").hide();
//             break;
//     }
// }