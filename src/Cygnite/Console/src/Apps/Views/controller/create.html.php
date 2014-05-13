{% extends 'layout/main/base.html.twig' %}

{% block title %}
    Cygnite Framework - Simple Crud Operation
{% endblock %}

{% block content %}


    {# dump(#controllerName#) #}

    <div style="float:right;margin-right:47px; margin-bottom: 10px;margin-top: 10px;">
    {{ addLink('#controllerName#', 'Back', buttonAttributes.primary) | raw }}
    </div>

    <div style="color:#FF0000;">
        {{ validation_errors | raw }}
    </div>

    <div style="float:left;">
        {{ createForm | raw }}
    </div>


{% endblock %}

{% block footer %}

{% endblock %}