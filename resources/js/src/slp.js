function hideTopHostelBookingButton() {
    const button = document.getElementById('topHostelHideButton')
    if (button) {
        button.addEventListener(
            'click',
            (e) => e.target.closest("#topHostelSidebar").classList.remove('sticky-sm-top-hostel'))
    }
}

function renderTableOfContent() {
    const wrap = document.querySelector(".slp-wrap");

    var items = Array.from(wrap.querySelectorAll("h2, h3"))

    document.getElementById('table-of-contents')
        .appendChild(createNestedList(getItems(items)), 'ol')

    function createNestedList(list, tag) {
        tag = tag !== undefined ? tag : 'ol'
        const tocList = document.createElement(tag)

        list.forEach(function (item) {
            const tocItem = document.createElement('li')
            const link = document.createElement('a')

            link.appendChild(document.createTextNode(item.element.textContent))
            if (item.element.id) {
                link.href = '#' + item.element.id
            } else {
                const generatedId = slugify(getFirstWordsFromString(item.element.textContent, 4))
                link.href = '#' + generatedId
                item.element.setAttribute('id', generatedId)
            }
            link.title = item.element.textContent
            link.setAttribute("class", "cl-primary")

            tocItem.appendChild(link)
            if (item.child) {
                const nestedList = createNestedList(item.child, 'ul')
                tocItem.appendChild(nestedList)
            }

            tocList.appendChild(tocItem)
        });

        return tocList;
    }

    function getItems(items) {
        const result = [];
        const stack = [];

        for (const item of items) {
            const currentItem = createItem(item);

            findStackLevel(currentItem, stack)

            if (stack.length > 0) {
                const lastItem = stack[stack.length - 1];
                lastItem.child.push(currentItem);
            } else {
                result.push(currentItem);
            }

            stack.push(currentItem);
        }

        return result;
    }

    function createItem(element, parent) {
        return {
            child: [],
            log: 'tagName: ' + element.tagName + ', title: ' + element.textContent + ', id: ' + element.getAttribute('id'),
            element: element,
            parent: parent,
            tagLevel: parseInt(element.tagName.charAt(1)),
        };
    }

    function findStackLevel(currentItem, stack) {
        while (stack.length > 0) {
            const lastItem = stack[stack.length - 1];
            // If the current item's tag level is greater than the last item's tag level, break the loop.
            // it mean the current item is a child for the last item
            if (currentItem.tagLevel > lastItem.tagLevel) {
                break;
            }

            // If the tag level of the current item is less than or equal to the last item's tag level,
            // it means the current item is a sibling or a sibling of an ancestor of the last item.
            stack.pop();
        }
    }
}

export {hideTopHostelBookingButton, renderTableOfContent}