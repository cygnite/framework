{% extends 'layout/main/base.html.twig' %}

{% block title %}
    Cygnite Framework - Simple Crud Operation
{% endblock %}

{% block content %}

    {# dump(#controllerName#) #}

    <div style="float:right;margin-right:47px; margin-bottom: 10px;margin-top: 10px;padding-bottom:30px;">
        {{ addLink('#controllerName#', 'Back', buttonAttributes.primary) | raw }}
    </div>



    <div class="form" style="">
        <h2>Showing #controllerName# #{{ record.id }}</h2>

        {#recordDivElements#}

    </div>


{% endblock %}

{% block footer %}

{% endblock %}
