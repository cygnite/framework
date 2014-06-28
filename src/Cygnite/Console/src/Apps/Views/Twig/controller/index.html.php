{% extends 'layout/main/base.html.twig' %}

{% block title %}
    Cygnite Framework - Simple Crud Operation
{% endblock %}

{% block content %}

    {# dump(records) #}

    <div style="margin-left: 79%;margin-bottom: 10px;margin-top: 10px;">
        {{ link('#controllerName#.type', 'Add #ControllerName#', buttonAttributes.primary) | raw }}
    </div>

    <table cellspacing="0" id="dataTable" cellpadding="0" style="width:890px;margin:0px auto;" class="tablesorter data-grid">
        <thead>
        <tr>
            <th>Sl No.</th>
            {#thColumns#}
            <th class="sorter-false">Action</th>
        </tr>
        </thead>

        <tbody>
        {%  if records|length > 0 %}

            {% for key, row in records %}

                {% if loop.index % 2 == 0 %}
                    {%  set rowType = 'even' %}
                {% else %}
                    {%  set rowType = 'odd' %}
                {% endif %}

                <tr class='{{ rowType }}'>
                    <td> {{ loop.index }}</td>

                    {#tdColumns#}

                    <td>
                        {{ link('#controllerName#.show.' ~ row.id ~ '/' ~ pageNumber , 'View' | upper, buttonAttributes.primary) | raw }}
                        {{ link('#controllerName#.type.' ~ row.id ~ '/' ~ pageNumber , 'Edit' | upper, buttonAttributes.primary) | raw }}
                        {{ link('#controllerName#.delete.' ~ row.id ~ '/' ~ pageNumber  , 'Delete' | upper, buttonAttributes.delete) | raw }}

                    </td>
                </tr>

            {% endfor %}
        {% else %}
            No records found !
        {% endif  %}
        </tbody>


    </table>

    <div >{{ links |raw }} </div>


{% endblock %}

{% block footer %}

{% endblock %}